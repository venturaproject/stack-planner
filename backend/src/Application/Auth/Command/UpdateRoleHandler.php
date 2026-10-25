<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\RoleResponse;
use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Exception\RoleNotFoundException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use App\Domain\Auth\Repository\RoleRepositoryInterface;

final class UpdateRoleHandler
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly PermissionRepositoryInterface $permissions,
    ) {
    }

    public function handle(UpdateRoleCommand $cmd): RoleResponse
    {
        $role = $this->roles->findById($cmd->id)
            ?? throw new RoleNotFoundException();

        $role->setName($cmd->name);
        $role->setDescription($cmd->description);

        $previousPermissionIds = array_map(
            static fn (Permission $permission): string => $permission->requireId(),
            [...$role->getPermissions()]
        );
        sort($previousPermissionIds);
        $newPermissionIds = $cmd->permissionIds;
        sort($newPermissionIds);

        if ($previousPermissionIds !== $newPermissionIds) {
            $role->setPermissions(array_values($this->permissions->findByIds($cmd->permissionIds)));
            $role->recordPermissionsChanged();
        }

        return RoleResponse::from($this->roles->save($role));
    }
}
