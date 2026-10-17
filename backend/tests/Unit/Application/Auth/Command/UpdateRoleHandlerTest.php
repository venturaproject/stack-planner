<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\UpdateRoleCommand;
use App\Application\Auth\Command\UpdateRoleHandler;
use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Event\RolePermissionsChanged;
use App\Domain\Auth\Exception\RoleNotFoundException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class UpdateRoleHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testUpdatesNameAndDescription(): void
    {
        $role = new Role();
        $role->setName('old-name');
        self::setId($role, 'role-1');

        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findById')->willReturn($role);
        $roles->method('save')->willReturnArgument(0);
        $permissions = $this->createStub(PermissionRepositoryInterface::class);

        $handler = new UpdateRoleHandler($roles, $permissions);

        $response = $handler->handle(new UpdateRoleCommand('role-1', 'new-name', 'new description'));

        self::assertSame('new-name', $response->name);
        self::assertSame('new description', $response->description);
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findById')->willReturn(null);
        $permissions = $this->createStub(PermissionRepositoryInterface::class);

        $handler = new UpdateRoleHandler($roles, $permissions);

        $this->expectException(RoleNotFoundException::class);

        $handler->handle(new UpdateRoleCommand('missing', 'name', null));
    }

    public function testRenamingAloneDoesNotRaisePermissionsChanged(): void
    {
        $role = new Role();
        $role->setName('old-name');
        self::setId($role, 'role-1');

        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findById')->willReturn($role);
        $roles->method('save')->willReturnArgument(0);
        $permissions = $this->createStub(PermissionRepositoryInterface::class);

        $handler = new UpdateRoleHandler($roles, $permissions);
        $handler->handle(new UpdateRoleCommand('role-1', 'new-name', null));

        self::assertSame([], $role->pullEvents());
    }

    public function testChangingPermissionsRaisesPermissionsChanged(): void
    {
        $role = new Role();
        $role->setName('editor');
        self::setId($role, 'role-1');

        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findById')->willReturn($role);
        $roles->method('save')->willReturnArgument(0);

        $permission = new Permission();
        self::setId($permission, 'perm-1');
        $permission->setName('users.view');
        // A mock, not a stub: with() has no effect on a stub (deprecated since
        // PHPUnit 12, removed in 13) — argument matching needs a real mock.
        $permissions = $this->createMock(PermissionRepositoryInterface::class);
        $permissions->expects(self::once())->method('findByIds')->with(['perm-1'])->willReturn([$permission]);

        $handler = new UpdateRoleHandler($roles, $permissions);
        $handler->handle(new UpdateRoleCommand('role-1', 'editor', null, ['perm-1']));

        $events = $role->pullEvents();
        self::assertInstanceOf(RolePermissionsChanged::class, $events[0]);
        self::assertSame(['users.view'], $events[0]->permissionNames);
    }
}
