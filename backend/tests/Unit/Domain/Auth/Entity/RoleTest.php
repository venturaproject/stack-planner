<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Auth\Entity;

use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Event\RolePermissionsChanged;
use App\Tests\Support\EntityIdSetter;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    use EntityIdSetter;

    public function testCreatedAtIsSetOnConstruction(): void
    {
        self::assertInstanceOf(DateTimeImmutable::class, (new Role())->getCreatedAt());
    }

    public function testAddPermissionAppendsToIterable(): void
    {
        $role = new Role();
        $permission = new Permission();
        $permission->setName('orders.view');

        $role->addPermission($permission);

        self::assertCount(1, $role->getPermissions());
        self::assertSame($permission, [...$role->getPermissions()][0]);
    }

    public function testRecordPermissionsChangedRaisesEventWithPermissionNames(): void
    {
        $role = new Role();
        $role->setName('editor');
        self::setId($role, 'role-1');
        $permission = new Permission();
        $permission->setName('orders.view');
        $role->addPermission($permission);

        $role->recordPermissionsChanged();

        $events = $role->pullEvents();
        self::assertInstanceOf(RolePermissionsChanged::class, $events[0]);
        self::assertSame(['orders.view'], $events[0]->permissionNames);
    }
}
