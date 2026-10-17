<?php

declare(strict_types=1);

namespace App\Application\Auth\DTO;

use App\Domain\Auth\Entity\Role;

final readonly class RoleResponse
{
    /** @param PermissionResponse[] $permissions */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public array $permissions,
        public int $permissionsCount,
        public int $usersCount,
        public string $guardName,
    ) {
    }

    public static function from(Role $role, int $usersCount = 0): self
    {
        $permissions = array_map(
            static fn ($p) => PermissionResponse::from($p),
            [...$role->getPermissions()]
        );

        return new self(
            id: $role->requireId(),
            name: $role->getName(),
            description: $role->getDescription(),
            permissions: $permissions,
            permissionsCount: \count($permissions),
            usersCount: $usersCount,
            guardName: 'api',
        );
    }
}
