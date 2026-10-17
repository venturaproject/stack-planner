<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Application\Shared\Outbox\EmailIdempotencyGuardInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Uid\Uuid;

/**
 * Sending an email isn't something a transaction can roll back — unlike
 * AuditLogRecorder/CreateNotificationHandler, whose "did I already do this?"
 * check and the effect it's guarding both live in the same database, there's no
 * way to atomically send an email and record that fact together. So this can
 * only give at-least-once delivery, not exactly-once: two workers both passing
 * alreadySent() before either calls markSent() (a redelivered event raced by
 * two consumers, or one crashing between send and mark) still means two sends.
 * There's no mail-provider-side idempotency key plumbed through here to close
 * that window — deliberately: it's a real limit, not an oversight, and it's an
 * acceptable one for these two emails specifically (an activation link that's
 * itself single-use once followed, and a purely informational notice) — this
 * check exists to make redelivery-triggered duplicates rare, not impossible.
 * Don't reach for this pattern for an email where an occasional duplicate would
 * actually matter; that needs a provider-level idempotency key instead.
 */
final class EmailIdempotencyGuard implements EmailIdempotencyGuardInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function alreadySent(?string $outboxEventId): bool
    {
        if (null === $outboxEventId) {
            return false;
        }

        return false !== $this->connection->fetchOne(
            'SELECT 1 FROM sent_emails WHERE outbox_event_id = :outboxEventId',
            ['outboxEventId' => $outboxEventId],
        );
    }

    public function markSent(?string $outboxEventId): void
    {
        if (null === $outboxEventId) {
            return;
        }

        try {
            // Postgres aborts the whole current transaction on a constraint
            // violation, not just the one failed statement — wrapping the insert
            // with transactional() (not a bare insert()) matters whenever this runs
            // inside an ambient transaction (e.g. command.bus's doctrine_transaction
            // middleware, if this is ever called from a command handler):
            // transactional() automatically scopes begin/commit/rollback to a
            // savepoint when already nested, so catching the violation below
            // doesn't leave the outer transaction poisoned and every later
            // statement on it failing with "current transaction is aborted".
            $this->connection->transactional(function () use ($outboxEventId): void {
                $this->connection->insert('sent_emails', [
                    'id' => Uuid::v7()->toRfc4122(),
                    'outbox_event_id' => $outboxEventId,
                    'sent_at' => (new DateTimeImmutable())->format('Y-m-d H:i:sP'),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // Another worker raced this one between alreadySent()'s check and this
            // insert (both saw "not sent yet" for the same redelivered event) and
            // won — the email is marked either way, which is all this method
            // promises. Not a failure: letting it propagate would make
            // OutboxConsumer treat an event that was actually handled successfully
            // as failed and schedule a pointless retry.
        }
    }
}
