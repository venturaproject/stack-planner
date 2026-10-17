<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Customer\Query\CustomerReadModelInterface;
use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\Repository\CustomerRepositoryInterface;
use App\Domain\Customer\ValueObject\Email;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 */
class DoctrineCustomerRepository extends ServiceEntityRepository implements CustomerRepositoryInterface, CustomerReadModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function findById(int $id): ?Customer
    {
        return $this->find($id);
    }

    public function findByEmail(Email $email): ?Customer
    {
        return $this->findOneBy(['email' => $email->value()]);
    }

    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('c.id', 'ASC')
            ->getQuery()->getResult();
    }

    public function findForExport(?string $search, ?bool $hasAddress, int $limit, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): array
    {
        $qb = $this->createQueryBuilder('c')->orderBy('c.id', 'ASC')->setMaxResults($limit);
        $this->applyFilters($qb, $search, $hasAddress, $createdFrom, $createdTo);

        return $qb->getQuery()->getResult();
    }

    public function findPaginated(int $page, int $limit, ?string $search, ?bool $hasAddress = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): array
    {
        $qb = $this->createQueryBuilder('c')->orderBy('c.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $this->applyFilters($qb, $search, $hasAddress, $createdFrom, $createdTo);

        return $qb->getQuery()->getResult();
    }

    public function countAll(?string $search, ?bool $hasAddress = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): int
    {
        $qb = $this->createQueryBuilder('c')->select('COUNT(c.id)');

        $this->applyFilters($qb, $search, $hasAddress, $createdFrom, $createdTo);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyFilters(QueryBuilder $qb, ?string $search, ?bool $hasAddress, ?DateTimeImmutable $createdFrom, ?DateTimeImmutable $createdTo): void
    {
        if (null !== $search && '' !== $search) {
            $qb->andWhere('LOWER(c.name) LIKE :s OR LOWER(c.email) LIKE :s')
                ->setParameter('s', '%'.strtolower($search).'%');
        }

        if (true === $hasAddress) {
            $qb->andWhere('c.addressStreet IS NOT NULL');
        } elseif (false === $hasAddress) {
            $qb->andWhere('c.addressStreet IS NULL');
        }

        if (null !== $createdFrom) {
            $qb->andWhere('c.createdAt >= :createdFrom')->setParameter('createdFrom', $createdFrom);
        }
        if (null !== $createdTo) {
            $qb->andWhere('c.createdAt <= :createdTo')->setParameter('createdTo', $createdTo);
        }
    }

    public function countWithAddress(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.addressStreet IS NOT NULL')
            ->getQuery()->getSingleScalarResult();
    }

    public function save(Customer $customer): Customer
    {
        $this->getEntityManager()->persist($customer);
        $this->getEntityManager()->flush();

        return $customer;
    }
}
