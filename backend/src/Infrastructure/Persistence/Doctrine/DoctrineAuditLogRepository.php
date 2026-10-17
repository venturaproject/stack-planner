<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\AuditLog\DTO\AuditLogEntryResponse;
use App\Application\AuditLog\DTO\PagedAuditLogResponse;
use App\Application\AuditLog\Query\AuditLogReadModelInterface;
use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AuditLog> */
final class DoctrineAuditLogRepository extends ServiceEntityRepository implements AuditLogRepositoryInterface, AuditLogReadModelInterface
{
    private const GENESIS_HASH = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    // Arbitrary fixed key identifying "the audit hash chain" for pg_advisory_xact_lock.
    // Any bigint works — it just needs to be the same constant every caller uses so
    // they all contend for the same lock. Not a real primary key, doesn't need to be
    // generated or stored anywhere.
    public const HASH_CHAIN_LOCK_KEY = 8_427_190_562_713_004;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function save(AuditLog $entry): void
    {
        try {
            $this->getEntityManager()->persist($entry);
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException) {
            // AuditLogRecorder's existsByOutboxEventId() dedup check happens before
            // withHashChainLock() is acquired, leaving a window where two workers
            // racing the same redelivered event can both pass it and both reach
            // here. The unique index on outbox_event_id is the real guard — losing
            // this race means the other worker's entry already exists, which is
            // the successful outcome this call was trying to reach anyway, not a
            // failure worth surfacing as one.
            //
            // This does leave the EntityManager closed (Doctrine's own behavior
            // after any flush() exception, regardless of whether it's caught) —
            // OutboxConsumer detects that after every event and exits the worker
            // cleanly for Supervisor to restart, see its own doc comment for why
            // that's the right recovery instead of trying to reset in place.
        }
    }

    public function getLastHash(): string
    {
        // Ordered by chainSequence, not createdAt/id: a UUIDv7's monotonicity
        // (id) is only guaranteed within the single PHP process that generated
        // it (Symfony's UuidV7 keeps that guarantee via static state private to
        // one process) — two different outbox worker processes generating one
        // each within the same millisecond have no ordering relationship to each
        // other at all, even though withHashChainLock() serializes them. A real
        // Postgres sequence has no such gap: nextval() only runs when save()'s
        // INSERT actually executes, which can't happen until this worker holds
        // the lock, and no other worker can acquire it until this transaction
        // ends — so sequence values are handed out in exactly the order writers
        // held the lock in, regardless of process or timestamp.
        $result = $this->createQueryBuilder('al')
            ->select('al.hash')
            ->orderBy('al.chainSequence', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $hash = \is_array($result) ? ($result['hash'] ?? null) : null;

        return \is_string($hash) ? $hash : self::GENESIS_HASH;
    }

    public function withHashChainLock(callable $fn): mixed
    {
        return $this->getEntityManager()->wrapInTransaction(function () use ($fn): mixed {
            // pg_advisory_xact_lock blocks until it can acquire the lock and releases it
            // automatically at the end of the current transaction (commit or rollback) —
            // no separate unlock call needed, and it can't be left stuck held if $fn throws.
            $this->getEntityManager()->getConnection()->executeQuery(
                'SELECT pg_advisory_xact_lock(:key)',
                ['key' => self::HASH_CHAIN_LOCK_KEY],
            );

            return $fn();
        });
    }

    public function existsByOutboxEventId(string $outboxEventId): bool
    {
        return null !== $this->createQueryBuilder('al')
            ->select('1')
            ->where('al.outboxEventId = :outboxEventId')
            ->setParameter('outboxEventId', $outboxEventId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByActorId(string $actorId, int $page, int $size): PagedAuditLogResponse
    {
        $qb = $this->createQueryBuilder('al')
            ->where('al.actorId = :actorId')
            ->setParameter('actorId', $actorId)
            ->orderBy('al.createdAt', 'DESC');

        $total = (int) (clone $qb)
            ->select('COUNT(al.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        /** @var AuditLog[] $entries */
        $entries = $qb
            ->setFirstResult(($page - 1) * $size)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult();

        return new PagedAuditLogResponse(
            data: array_map(AuditLogEntryResponse::from(...), $entries),
            total: $total,
            page: $page,
            lastPage: $total > 0 ? (int) ceil($total / $size) : 1,
            perPage: $size,
        );
    }
}
