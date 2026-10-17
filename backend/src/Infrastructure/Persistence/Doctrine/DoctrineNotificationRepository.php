<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Notification\Query\NotificationReadModelInterface;
use App\Domain\Notification\Entity\Notification;
use App\Domain\Notification\Repository\NotificationRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class DoctrineNotificationRepository extends ServiceEntityRepository implements NotificationRepositoryInterface, NotificationReadModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findById(string $id): ?Notification
    {
        return $this->find($id);
    }

    public function findByOutboxEventId(string $outboxEventId): ?Notification
    {
        return $this->findOneBy(['outboxEventId' => $outboxEventId]);
    }

    /** @return list<Notification> */
    public function findForUser(string $userId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.userId = :userId OR n.userId IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Notification $notification): Notification
    {
        try {
            $this->getEntityManager()->persist($notification);
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException) {
            // CreateNotificationHandler's findByOutboxEventId() dedup check has no
            // lock backing it (unlike AuditLogRecorder's), so two workers racing
            // the same redelivered event can both pass it and both reach here. The
            // unique index on outbox_event_id is the real guard — losing this race
            // means the other worker's notification already exists, which is the
            // successful outcome this call was trying to reach anyway, not a
            // failure worth surfacing as one. $notification's id was already
            // assigned by the UUID generator at persist() time even though this
            // particular flush() never committed it — returning it as-is (rather
            // than re-querying, which the now-closed EntityManager can't safely do
            // anyway) is fine here: CreateNotificationCommand's callers all
            // dispatch-and-discard, nothing reads this return value.
            //
            // This does leave the EntityManager closed (Doctrine's own behavior
            // after any flush() exception, regardless of whether it's caught) —
            // OutboxConsumer detects that after every event and exits the worker
            // cleanly for Supervisor to restart, see its own doc comment for why
            // that's the right recovery instead of trying to reset in place.
        }

        return $notification;
    }

    public function delete(Notification $notification): void
    {
        $this->getEntityManager()->remove($notification);
        $this->getEntityManager()->flush();
    }

    public function deleteAllForUser(string $userId): void
    {
        $this->createQueryBuilder('n')
            ->delete()
            ->where('n.userId = :userId OR n.userId IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function markAllReadForUser(string $userId): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.readAt', ':now')
            ->where('(n.userId = :userId OR n.userId IS NULL) AND n.readAt IS NULL')
            ->setParameter('userId', $userId)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
