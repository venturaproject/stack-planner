<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Permission>
 */
class DoctrinePermissionRepository extends ServiceEntityRepository implements PermissionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    public function findById(string $id): ?Permission
    {
        return $this->find($id);
    }

    public function findByName(string $name): ?Permission
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function save(Permission $permission): Permission
    {
        $this->getEntityManager()->persist($permission);
        $this->getEntityManager()->flush();

        return $permission;
    }

    public function delete(Permission $permission): void
    {
        $this->getEntityManager()->remove($permission);
        $this->getEntityManager()->flush();
    }
}
