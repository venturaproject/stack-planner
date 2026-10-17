<?php

declare(strict_types=1);

namespace App\Application\Auth\Query;

use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Entity\User;

interface AuthReadModelInterface
{
    public function findUserById(string $id): ?User;

    /** @return list<User> */
    public function findUsersPaginated(int $page, int $limit): array;

    public function countUsers(): int;

    public function findRoleById(string $id): ?Role;

    /** @return list<Role> */
    public function findRolesPaginated(int $page, int $limit): array;

    public function countRoles(): int;

    /** @return list<Permission> */
    public function findPermissionsPaginated(int $page, int $limit): array;

    public function countPermissions(): int;
}
