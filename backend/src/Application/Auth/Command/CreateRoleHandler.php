<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\RoleResponse;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Exception\RoleAlreadyExistsException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class CreateRoleHandler
{
    public function __construct(
        private readonly RoleRepositoryInterface $roles,
        private readonly PermissionRepositoryInterface $permissions,
    ) {
    }

    public function handle(CreateRoleCommand $cmd): RoleResponse
    {
        if (null !== $this->roles->findByName($cmd->name)) {
            throw new RoleAlreadyExistsException($cmd->name);
        }

        $role = new Role();
        $role->setName($cmd->name);
        $role->setDescription($cmd->description);
        $role->recordCreated();

        // recordPermissionsChanged() requires a persisted id (it's also used from
        // UpdateRoleHandler, where the role always already has one), so an initial
        // permission set can't be applied and raised in the same pass as creation:
        // the role is saved first to get its id, then permissions are applied in a
        // second save() — whose flush is what actually gets RolePermissionsChanged
        // to the outbox (DoctrineOutboxListener only fires on postPersist/postUpdate).
        $this->roles->save($role);

        if ([] !== $cmd->permissionIds) {
            $role->setPermissions(array_values($this->permissions->findByIds($cmd->permissionIds)));
            $role->recordPermissionsChanged();
            $this->roles->save($role);
        }

        return RoleResponse::from($role);
    }
}
