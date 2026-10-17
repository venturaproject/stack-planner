<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Domain\Auth\Exception\RoleNotFoundException;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class DeleteRoleHandler
{
    public function __construct(private readonly RoleRepositoryInterface $roles)
    {
    }

    public function handle(DeleteRoleCommand $cmd): void
    {
        $this->roles->delete(
            $this->roles->findById($cmd->id) ?? throw new RoleNotFoundException()
        );
    }
}
