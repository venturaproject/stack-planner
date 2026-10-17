<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\DeleteRoleCommand;
use App\Application\Auth\Command\DeleteRoleHandler;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Exception\RoleNotFoundException;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteRoleHandlerTest extends TestCase
{
    public function testDeletesExistingRole(): void
    {
        $role = new Role();
        $roles = $this->createMock(RoleRepositoryInterface::class);
        $roles->expects(self::once())->method('findById')->with('role-1')->willReturn($role);
        $roles->expects(self::once())->method('delete')->with($role);

        $handler = new DeleteRoleHandler($roles);

        $handler->handle(new DeleteRoleCommand('role-1'));
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $roles = $this->createMock(RoleRepositoryInterface::class);
        $roles->method('findById')->willReturn(null);
        $roles->expects(self::never())->method('delete');

        $handler = new DeleteRoleHandler($roles);

        $this->expectException(RoleNotFoundException::class);

        $handler->handle(new DeleteRoleCommand('missing'));
    }
}
