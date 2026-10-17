<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\DeletePermissionCommand;
use App\Application\Auth\Command\DeletePermissionHandler;
use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Exception\PermissionNotFoundException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeletePermissionHandlerTest extends TestCase
{
    public function testDeletesExistingPermission(): void
    {
        $permission = new Permission();
        $permissions = $this->createMock(PermissionRepositoryInterface::class);
        $permissions->expects(self::once())->method('findById')->with('perm-1')->willReturn($permission);
        $permissions->expects(self::once())->method('delete')->with($permission);

        $handler = new DeletePermissionHandler($permissions);

        $handler->handle(new DeletePermissionCommand('perm-1'));
    }

    public function testThrowsWhenPermissionNotFound(): void
    {
        $permissions = $this->createMock(PermissionRepositoryInterface::class);
        $permissions->method('findById')->willReturn(null);
        $permissions->expects(self::never())->method('delete');

        $handler = new DeletePermissionHandler($permissions);

        $this->expectException(PermissionNotFoundException::class);

        $handler->handle(new DeletePermissionCommand('missing'));
    }
}
