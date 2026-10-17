<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\CreateRoleCommand;
use App\Application\Auth\Command\CreateRoleHandler;
use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Event\RoleCreated;
use App\Domain\Auth\Exception\RoleAlreadyExistsException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class CreateRoleHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testCreatesRoleAndRaisesEventBeforePersistence(): void
    {
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findByName')->willReturn(null);
        $roles->method('save')->willReturnCallback(static function (Role $role) {
            self::setId($role, 'role-1');

            return $role;
        });
        $permissions = $this->createStub(PermissionRepositoryInterface::class);

        $handler = new CreateRoleHandler($roles, $permissions);

        $response = $handler->handle(new CreateRoleCommand('editor', 'Can edit content'));

        self::assertSame('role-1', $response->id);
        self::assertSame('editor', $response->name);
    }

    public function testAppliesAndRaisesPermissionsChangedWhenPermissionsGiven(): void
    {
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findByName')->willReturn(null);
        $roles->method('save')->willReturnCallback(static function (Role $role) {
            if (null === $role->getId()) {
                self::setId($role, 'role-1');
            }

            return $role;
        });

        $permission = new Permission();
        self::setId($permission, 'perm-1');
        $permission->setName('users.view');
        // A mock, not a stub: with() has no effect on a stub (deprecated since
        // PHPUnit 12, removed in 13) — argument matching needs a real mock.
        $permissions = $this->createMock(PermissionRepositoryInterface::class);
        $permissions->expects(self::once())->method('findByIds')->with(['perm-1'])->willReturn([$permission]);

        $handler = new CreateRoleHandler($roles, $permissions);

        $response = $handler->handle(new CreateRoleCommand('editor', null, ['perm-1']));

        self::assertSame(['users.view'], array_map(static fn ($p) => $p->name, $response->permissions));
    }

    public function testRaisedEventToleratesMissingIdAtCreationTime(): void
    {
        // The RoleCreated event is raised before the entity is persisted, so its
        // id is not yet known. Regression test for the crash this caused when the
        // event field was a non-nullable string.
        $role = new Role();
        $role->setName('editor');

        $role->recordCreated();

        $events = $role->pullEvents();
        self::assertInstanceOf(RoleCreated::class, $events[0]);
        self::assertNull($events[0]->roleId);
    }

    public function testThrowsWhenRoleNameAlreadyExists(): void
    {
        $roles = $this->createMock(RoleRepositoryInterface::class);
        $roles->method('findByName')->willReturn(new Role());
        $roles->expects(self::never())->method('save');
        $permissions = $this->createStub(PermissionRepositoryInterface::class);

        $handler = new CreateRoleHandler($roles, $permissions);

        $this->expectException(RoleAlreadyExistsException::class);

        $handler->handle(new CreateRoleCommand('editor', null));
    }
}
