<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\PermissionResponse;
use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Exception\PermissionAlreadyExistsException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class CreatePermissionHandler
{
    public function __construct(private readonly PermissionRepositoryInterface $permissions)
    {
    }

    public function handle(CreatePermissionCommand $cmd): PermissionResponse
    {
        if (null !== $this->permissions->findByName($cmd->name)) {
            throw new PermissionAlreadyExistsException($cmd->name);
        }

        $permission = new Permission();
        $permission->setName($cmd->name);
        $permission->setDescription($cmd->description);

        return PermissionResponse::from($this->permissions->save($permission));
    }
}
