<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence\Doctrine;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\DoctrineAuditLogRepository;
use App\Tests\Support\DatabaseKernelTestCase;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

/**
 * Verifies withHashChainLock() actually serializes concurrent callers at the
 * database level, using a second, independent connection to observe the lock
 * from outside the test's own (DAMA-wrapped, uncommitted) transaction — real
 * concurrency can't be simulated within one single-threaded PHP process, but
 * Postgres's own pg_try_advisory_xact_lock lets us assert the lock state
 * directly instead.
 */
final class DoctrineAuditLogRepositoryTest extends DatabaseKernelTestCase
{
    private AuditLogRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(AuditLogRepositoryInterface::class);
    }

    private function secondConnection(): Connection
    {
        return DriverManager::getConnection($this->em->getConnection()->getParams());
    }

    public function testHoldsThePostgresAdvisoryLockForTheCallbacksDuration(): void
    {
        $second = $this->secondConnection();
        $second->beginTransaction();

        try {
            $heldByMainConnection = false;

            $this->repository->withHashChainLock(static function () use ($second, &$heldByMainConnection): void {
                // While the main connection's callback is running (and therefore
                // holding the lock, per DoctrineAuditLogRepository::withHashChainLock),
                // a second, unrelated connection trying to take the same lock
                // key must be refused it.
                $acquired = (bool) $second->fetchOne(
                    'SELECT pg_try_advisory_xact_lock(:key)',
                    ['key' => DoctrineAuditLogRepository::HASH_CHAIN_LOCK_KEY],
                );
                $heldByMainConnection = !$acquired;
            });

            self::assertTrue($heldByMainConnection, 'A concurrent connection should not have been able to acquire the hash-chain lock while it was held.');

            // Not asserting the lock is free again after withHashChainLock() returns:
            // DAMA\DoctrineTestBundle wraps this whole test in one outer transaction
            // that's only ever rolled back (never really committed) at the test's end,
            // so the underlying Postgres transaction withHashChainLock() opened is
            // still technically open here too — pg_advisory_xact_lock's release-on-
            // commit-or-rollback guarantee is real in actual usage, just not
            // observable from inside this test harness.
        } finally {
            $second->rollBack();
            $second->close();
        }
    }

    public function testReturnsTheCallbacksResult(): void
    {
        $result = $this->repository->withHashChainLock(static fn (): string => 'computed-value');

        self::assertSame('computed-value', $result);
    }

    public function testGetLastHashOrdersByChainSequenceNotByIdOrCreatedAt(): void
    {
        // A UUIDv7's monotonicity is only guaranteed within the single PHP process
        // that generated it (Symfony's UuidV7 keeps a "last generated" high-water
        // mark in static state private to that process) — two different outbox
        // worker processes each generating one within the same millisecond have no
        // ordering relationship to each other at all, even serialized one-at-a-time
        // by withHashChainLock(). This deliberately gives the second-inserted row
        // both an earlier createdAt *and* a lexicographically smaller id than the
        // first, simulating exactly that cross-process case, and leaves
        // chain_sequence off both inserts so its real column DEFAULT (a Postgres
        // sequence — see the migration) assigns it, same as it would outside a
        // test. getLastHash() must still identify the row actually inserted
        // second as the last one.
        $this->em->getConnection()->insert('audit_logs', [
            'id' => '00000000-0000-7000-8000-000000000002',
            'action' => 'test.first',
            'resource_type' => 'Test',
            'metadata' => '{}',
            'previous_hash' => str_repeat('0', 64),
            'hash' => 'first-hash-'.str_repeat('a', 53),
            'created_at' => (new DateTimeImmutable('2026-01-01 12:00:01'))->format('Y-m-d H:i:sP'),
        ]);
        $this->em->getConnection()->insert('audit_logs', [
            'id' => '00000000-0000-7000-8000-000000000001',
            'action' => 'test.second',
            'resource_type' => 'Test',
            'metadata' => '{}',
            'previous_hash' => 'first-hash-'.str_repeat('a', 53),
            'hash' => 'second-hash-'.str_repeat('b', 52),
            'created_at' => (new DateTimeImmutable('2026-01-01 12:00:00'))->format('Y-m-d H:i:sP'),
        ]);

        self::assertSame('second-hash-'.str_repeat('b', 52), $this->repository->getLastHash());
    }

    public function testSavingASecondEntryForAnAlreadyRecordedOutboxEventDoesNotThrow(): void
    {
        // The scenario this guards: AuditLogRecorder's existsByOutboxEventId()
        // dedup check runs before withHashChainLock() is acquired, so two workers
        // racing the same redelivered event (e.g. one claimed it again after the
        // other's lock lease expired mid-processing) can both pass that check and
        // both reach save(). The unique index on outbox_event_id is the real
        // guard; losing this race must not surface as a failure — the entry
        // already exists either way.
        $this->repository->save(new AuditLog(
            id: '00000000-0000-7000-8000-000000000010',
            actorId: null,
            actorEmail: null,
            action: 'test.first',
            resourceType: 'Test',
            resourceId: null,
            metadata: [],
            ipAddress: null,
            previousHash: str_repeat('0', 64),
            hash: 'race-hash-a-'.str_repeat('a', 52),
            createdAt: new DateTimeImmutable(),
            outboxEventId: 'racing-outbox-event',
        ));

        // Must not throw.
        $this->repository->save(new AuditLog(
            id: '00000000-0000-7000-8000-000000000011',
            actorId: null,
            actorEmail: null,
            action: 'test.second',
            resourceType: 'Test',
            resourceId: null,
            metadata: [],
            ipAddress: null,
            previousHash: 'race-hash-a-'.str_repeat('a', 52),
            hash: 'race-hash-b-'.str_repeat('b', 52),
            createdAt: new DateTimeImmutable(),
            outboxEventId: 'racing-outbox-event',
        ));

        // Doctrine's own behavior after any flush() exception, caught or not —
        // documented on the catch block this exercises, and on
        // OutboxWorkerMustRestartException, which is how OutboxConsumer reacts to
        // it in real usage.
        self::assertFalse($this->em->isOpen());
    }
}
