<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\PermissionResponse;
use App\Domain\Auth\Exception\PermissionNotFoundException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class UpdatePermissionHandler
{
    public function __construct(private readonly PermissionRepositoryInterface $permissions)
    {
    }

    public function handle(UpdatePermissionCommand $cmd): PermissionResponse
    {
        $permission = $this->permissions->findById($cmd->id)
            ?? throw new PermissionNotFoundException();

        $permission->setName($cmd->name);
        $permission->setDescription($cmd->description);

        return PermissionResponse::from($this->permissions->save($permission));
    }
}
