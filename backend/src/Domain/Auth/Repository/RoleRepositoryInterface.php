<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repository;

use App\Domain\Auth\Entity\Role;

interface RoleRepositoryInterface
{
    public function findById(string $id): ?Role;

    public function findByName(string $name): ?Role;

    public function save(Role $role): Role;

    public function delete(Role $role): void;
}
