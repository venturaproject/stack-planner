<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Domain\Auth\Exception\PermissionNotFoundException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;

final class DeletePermissionHandler
{
    public function __construct(private readonly PermissionRepositoryInterface $permissions)
    {
    }

    public function handle(DeletePermissionCommand $cmd): void
    {
        $this->permissions->delete(
            $this->permissions->findById($cmd->id) ?? throw new PermissionNotFoundException()
        );
    }
}
