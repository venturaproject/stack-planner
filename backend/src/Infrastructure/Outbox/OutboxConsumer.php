<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class OutboxConsumer
{
    // After this many failed attempts, an event stops being retried and is marked
    // failed_at instead — see app:outbox:failed / app:outbox:requeue to inspect and
    // reprocess it. Without a cap, a permanently-failing event (a bad payload, a bug
    // in its handler) would retry forever, indefinitely wasting consumer capacity and
    // burying it among genuinely-still-retrying events instead of surfacing it.
    private const MAX_ATTEMPTS = 10;

    public function __construct(
        private readonly Connection $connection,
        private readonly OutboxEventCodec $codec,
        private readonly AuditContext $auditContext,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function consumeOne(): bool
    {
        $row = $this->connection->transactional(function (): array|false {
            $row = $this->connection->fetchAssociative('SELECT * FROM outbox_events WHERE processed_at IS NULL AND failed_at IS NULL AND available_at <= NOW() AND (locked_until IS NULL OR locked_until < NOW()) ORDER BY occurred_at, id FOR UPDATE SKIP LOCKED LIMIT 1');
            if (false === $row) {
                return false;
            }

            $this->connection->executeStatement("UPDATE outbox_events SET attempts = attempts + 1, locked_until = NOW() + INTERVAL '5 minutes' WHERE id = :id", ['id' => $row['id']]);

            return $row;
        });

        if (false === $row) {
            return false;
        }

        /** @var array{id: string, payload: string, context: string, event_type: string, attempts: int} $row */
        $caught = null;

        try {
            $payload = json_decode($row['payload'], true, 512, \JSON_THROW_ON_ERROR);
            $context = json_decode($row['context'], true, 512, \JSON_THROW_ON_ERROR);
            if (!\is_array($payload) || !\is_array($context)) {
                throw new RuntimeException(\sprintf('Outbox event "%s" has a malformed payload or context.', $row['id']));
            }
            $this->auditContext->set($context);
            $this->auditContext->setOutboxEventId($row['id']);
            $event = $this->codec->decode($row['event_type'], $payload);
            $this->dispatcher->dispatch($event, $row['event_type']);
            $this->connection->executeStatement('UPDATE outbox_events SET processed_at = NOW(), locked_until = NULL, last_error = NULL WHERE id = :id', ['id' => $row['id']]);
        } catch (Throwable $exception) {
            $caught = $exception;
            $attempt = (int) $row['attempts'] + 1;
            $error = substr($exception->getMessage(), 0, 4000);

            if ($attempt >= self::MAX_ATTEMPTS) {
                $this->connection->executeStatement(
                    'UPDATE outbox_events SET locked_until = NULL, failed_at = NOW(), last_error = :error WHERE id = :id',
                    ['id' => $row['id'], 'error' => $error],
                );
            } else {
                $delay = min(300, 2 ** min($attempt, 8));
                $this->connection->executeStatement(
                    'UPDATE outbox_events SET locked_until = NULL, available_at = :availableAt, last_error = :error WHERE id = :id',
                    [
                        'id' => $row['id'],
                        'availableAt' => (new DateTimeImmutable(\sprintf('+%d seconds', $delay)))->format('Y-m-d H:i:sP'),
                        'error' => $error,
                    ],
                );
            }
        } finally {
            $this->auditContext->clear();
        }

        // A subscriber's flush() failure (a caught unique-constraint violation
        // that raced a redelivery, a dropped connection, anything) leaves
        // Doctrine's EntityManager permanently closed — there's no supported way
        // to keep using it, and repositories already injected elsewhere in this
        // worker process would keep resolving to that same stale instance even
        // after a registry reset. Bookkeeping for the row that just failed is
        // already done above (raw DBAL, unaffected by the ORM's EM state), so it's
        // safe to let this escape now and end the process — see the exception's
        // own doc comment for why that's the right recovery, not a workaround.
        if (!$this->entityManager->isOpen()) {
            throw new OutboxWorkerMustRestartException($caught);
        }

        return true;
    }
}
