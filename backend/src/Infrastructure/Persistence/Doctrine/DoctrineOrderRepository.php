<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Order\Query\OrderReadModelInterface;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Order\ValueObject\OrderStatus;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class DoctrineOrderRepository extends ServiceEntityRepository implements OrderRepositoryInterface, OrderReadModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findById(int $id): ?Order
    {
        return $this->find($id);
    }

    public function findByIdForUpdate(int $id): ?Order
    {
        return $this->find($id, LockMode::PESSIMISTIC_WRITE);
    }

    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('o')
            ->leftJoin('o.lines', 'l')
            ->addSelect('l')
            ->where('o.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('o.id', 'ASC')
            ->getQuery()->getResult();
    }

    public function findForExport(?int $customerId, ?OrderStatus $status, int $limit, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.lines', 'l')
            ->addSelect('l')
            ->orderBy('o.id', 'DESC')
            ->setMaxResults($limit);
        $this->applyFilters($qb, $customerId, $status, $createdFrom, $createdTo);

        return iterator_to_array(new Paginator($qb->getQuery(), true));
    }

    public function findPaginated(int $page, int $limit, ?int $customerId, ?OrderStatus $status = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.lines', 'l')
            ->addSelect('l')
            ->orderBy('o.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $this->applyFilters($qb, $customerId, $status, $createdFrom, $createdTo);

        return iterator_to_array(new Paginator($qb->getQuery(), true));
    }

    public function countAll(?int $customerId, ?OrderStatus $status = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): int
    {
        $qb = $this->createQueryBuilder('o')->select('COUNT(o.id)');

        $this->applyFilters($qb, $customerId, $status, $createdFrom, $createdTo);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyFilters(QueryBuilder $qb, ?int $customerId, ?OrderStatus $status, ?DateTimeImmutable $createdFrom, ?DateTimeImmutable $createdTo): void
    {
        if (null !== $customerId) {
            $qb->andWhere('o.customerId = :customerId')->setParameter('customerId', $customerId);
        }

        if (null !== $status) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        if (null !== $createdFrom) {
            $qb->andWhere('o.createdAt >= :createdFrom')->setParameter('createdFrom', $createdFrom);
        }
        if (null !== $createdTo) {
            $qb->andWhere('o.createdAt <= :createdTo')->setParameter('createdTo', $createdTo);
        }
    }

    public function countByStatus(OrderStatus $status): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()->getSingleScalarResult();
    }

    public function save(Order $order): Order
    {
        $this->getEntityManager()->persist($order);
        $this->getEntityManager()->flush();

        return $order;
    }
}
