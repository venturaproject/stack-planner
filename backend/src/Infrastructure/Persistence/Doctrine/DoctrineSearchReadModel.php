<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Search\Query\SearchReadModelInterface;
use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSearchReadModel implements SearchReadModelInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function search(string $term): array
    {
        $like = '%'.mb_strtolower($term).'%';

        /** @var list<User> $users */
        $users = $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('LOWER(u.firstName) LIKE :term OR LOWER(u.lastName) LIKE :term OR LOWER(u.email) LIKE :term')
            ->setParameter('term', $like)
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        /** @var list<Role> $roles */
        $roles = $this->entityManager->getRepository(Role::class)->createQueryBuilder('r')
            ->where('LOWER(r.name) LIKE :term')
            ->setParameter('term', $like)
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        /** @var list<Permission> $permissions */
        $permissions = $this->entityManager->getRepository(Permission::class)->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE :term')
            ->setParameter('term', $like)
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        return [
            'users' => array_map(static fn (User $user): array => [
                'id' => $user->requireId(),
                'name' => trim($user->getFirstName().' '.$user->getLastName()),
                'email' => $user->getEmail(),
            ], $users),
            'roles' => array_map(static fn (Role $role): array => [
                'id' => $role->requireId(),
                'name' => $role->getName(),
            ], $roles),
            'permissions' => array_map(static fn (Permission $permission): array => [
                'id' => $permission->requireId(),
                'name' => $permission->getName(),
                'guard_name' => 'api',
            ], $permissions),
            'categories' => [],
            'tags' => [],
        ];
    }
}
