<?php

declare(strict_types=1);

namespace App\Domain\AuditLog\Repository;

use App\Domain\AuditLog\Entity\AuditLog;

interface AuditLogRepositoryInterface
{
    public function save(AuditLog $entry): void;

    public function getLastHash(): string;

    /** Used to dedupe a redelivered outbox event before recording it again. */
    public function existsByOutboxEventId(string $outboxEventId): bool;

    /**
     * Runs $fn with an exclusive lock held for its duration, serializing
     * getLastHash()-then-save() across concurrent outbox consumers so two workers
     * processing different events at the same time can't both read the same
     * previousHash and each write a link claiming to follow it.
     *
     * @template T
     *
     * @param callable(): T $fn
     *
     * @return T
     */
    public function withHashChainLock(callable $fn): mixed;
}
