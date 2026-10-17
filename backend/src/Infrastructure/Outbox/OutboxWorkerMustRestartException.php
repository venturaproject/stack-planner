<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use RuntimeException;
use Throwable;

/**
 * Thrown by OutboxConsumer::consumeOne() when a flush failure has left Doctrine's
 * EntityManager closed. Doctrine closes it internally whenever a flush() throws
 * (a constraint violation, a dropped connection, ...) — there's no supported way
 * to keep using it afterward, and repositories already constructor-injected
 * elsewhere in this worker process (AuditLogRecorder's, NotificationRepository's,
 * ...) would keep resolving to that same stale, closed instance even after
 * ManagerRegistry::resetManager(), since Doctrine's ServiceEntityRepository
 * memoizes the EntityManager it resolved at construction time and never re-checks
 * it. The only reliable recovery is a fresh process, which is what letting this
 * escape ConsumeOutboxCommand and exit non-zero is for — see its doc comment.
 */
final class OutboxWorkerMustRestartException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            'The EntityManager was closed after a flush failure — this worker process can no longer make Doctrine calls reliably and must exit for Supervisor to restart it fresh.',
            0,
            $previous,
        );
    }
}
