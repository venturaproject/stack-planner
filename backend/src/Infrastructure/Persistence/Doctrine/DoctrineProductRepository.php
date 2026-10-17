<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Product\Query\ProductReadModelInterface;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class DoctrineProductRepository extends ServiceEntityRepository implements ProductRepositoryInterface, ProductReadModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findById(int $id): ?Product
    {
        return $this->find($id);
    }

    public function findByIdForUpdate(int $id): ?Product
    {
        return $this->find($id, LockMode::PESSIMISTIC_WRITE);
    }

    public function findBySku(Sku $sku): ?Product
    {
        return $this->findOneBy(['sku' => $sku->value()]);
    }

    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('p.id', 'ASC')
            ->getQuery()->getResult();
    }

    public function findForExport(?string $search, ?string $stockLevel, int $limit, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null, ?string $stockStatus = null, ?int $priceMin = null, ?int $priceMax = null): array
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.id', 'ASC')->setMaxResults($limit);
        $this->applyFilters($qb, $search, $stockLevel, $createdFrom, $createdTo, $stockStatus, $priceMin, $priceMax);

        return $qb->getQuery()->getResult();
    }

    public function findPaginated(int $page, int $limit, ?string $search, ?string $stockLevel = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null, ?string $stockStatus = null, ?int $priceMin = null, ?int $priceMax = null): array
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $this->applyFilters($qb, $search, $stockLevel, $createdFrom, $createdTo, $stockStatus, $priceMin, $priceMax);

        return $qb->getQuery()->getResult();
    }

    public function countAll(?string $search, ?string $stockLevel = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null, ?string $stockStatus = null, ?int $priceMin = null, ?int $priceMax = null): int
    {
        $qb = $this->createQueryBuilder('p')->select('COUNT(p.id)');

        $this->applyFilters($qb, $search, $stockLevel, $createdFrom, $createdTo, $stockStatus, $priceMin, $priceMax);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyFilters(QueryBuilder $qb, ?string $search, ?string $stockLevel, ?DateTimeImmutable $createdFrom, ?DateTimeImmutable $createdTo, ?string $stockStatus = null, ?int $priceMin = null, ?int $priceMax = null): void
    {
        if (null !== $search && '' !== $search) {
            $qb->andWhere('LOWER(p.name) LIKE :s OR LOWER(p.sku) LIKE :s')
                ->setParameter('s', '%'.strtolower($search).'%');
        }

        if ('low' === $stockLevel) {
            $qb->andWhere('p.stockQuantity > 0 AND p.stockQuantity <= 5');
        } elseif ('out' === $stockLevel) {
            $qb->andWhere('p.stockQuantity <= 0');
        }

        if ('positive' === $stockStatus) {
            $qb->andWhere('p.stockQuantity > 0');
        } elseif ('zero' === $stockStatus) {
            $qb->andWhere('p.stockQuantity = 0');
        } elseif ('negative' === $stockStatus) {
            $qb->andWhere('p.stockQuantity < 0');
        }

        if (null !== $createdFrom) {
            $qb->andWhere('p.createdAt >= :createdFrom')->setParameter('createdFrom', $createdFrom);
        }
        if (null !== $createdTo) {
            $qb->andWhere('p.createdAt <= :createdTo')->setParameter('createdTo', $createdTo);
        }

        if (null !== $priceMin) {
            $qb->andWhere('p.priceAmountMinorUnits >= :priceMin')->setParameter('priceMin', $priceMin);
        }
        if (null !== $priceMax) {
            $qb->andWhere('p.priceAmountMinorUnits <= :priceMax')->setParameter('priceMax', $priceMax);
        }
    }

    public function countLowStock(int $threshold): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.stockQuantity <= :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()->getSingleScalarResult();
    }

    public function save(Product $product): Product
    {
        $this->getEntityManager()->persist($product);
        $this->getEntityManager()->flush();

        return $product;
    }
}
