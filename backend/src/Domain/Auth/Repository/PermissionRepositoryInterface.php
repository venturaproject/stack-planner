<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repository;

use App\Domain\Auth\Entity\Permission;

interface PermissionRepositoryInterface
{
    public function findById(string $id): ?Permission;

    public function findByName(string $name): ?Permission;

    /**
     * @param string[] $ids
     *
     * @return Permission[]
     */
    public function findByIds(array $ids): array;

    public function save(Permission $permission): Permission;

    public function delete(Permission $permission): void;
}
