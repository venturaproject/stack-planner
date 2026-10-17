<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class DoctrineRoleRepository extends ServiceEntityRepository implements RoleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function findById(string $id): ?Role
    {
        return $this->find($id);
    }

    public function findByName(string $name): ?Role
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function save(Role $role): Role
    {
        $this->getEntityManager()->persist($role);
        $this->getEntityManager()->flush();

        return $role;
    }

    public function delete(Role $role): void
    {
        $this->getEntityManager()->remove($role);
        $this->getEntityManager()->flush();
    }
}
