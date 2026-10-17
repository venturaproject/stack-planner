<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Auth\Query\AuthReadModelInterface;
use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAuthReadModel implements AuthReadModelInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function findUserById(string $id): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    /** @return list<User> */
    public function findUsersPaginated(int $page, int $limit): array
    {
        return $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUsers(): int
    {
        return $this->entityManager->getRepository(User::class)->count([]);
    }

    public function findRoleById(string $id): ?Role
    {
        return $this->entityManager->getRepository(Role::class)->find($id);
    }

    /** @return list<Role> */
    public function findRolesPaginated(int $page, int $limit): array
    {
        return $this->entityManager->getRepository(Role::class)->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countRoles(): int
    {
        return $this->entityManager->getRepository(Role::class)->count([]);
    }

    /** @return list<Permission> */
    public function findPermissionsPaginated(int $page, int $limit): array
    {
        return $this->entityManager->getRepository(Permission::class)->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPermissions(): int
    {
        return $this->entityManager->getRepository(Permission::class)->count([]);
    }
}
