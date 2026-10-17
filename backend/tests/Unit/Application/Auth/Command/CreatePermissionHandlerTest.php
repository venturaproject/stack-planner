<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\CreatePermissionCommand;
use App\Application\Auth\Command\CreatePermissionHandler;
use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Exception\PermissionAlreadyExistsException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class CreatePermissionHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testCreatesPermission(): void
    {
        $permissions = $this->createStub(PermissionRepositoryInterface::class);
        $permissions->method('findByName')->willReturn(null);
        $permissions->method('save')->willReturnCallback(static function (Permission $permission) {
            self::setId($permission, 'perm-1');

            return $permission;
        });

        $handler = new CreatePermissionHandler($permissions);

        $response = $handler->handle(new CreatePermissionCommand('orders.view', 'View orders'));

        self::assertSame('perm-1', $response->id);
        self::assertSame('orders.view', $response->name);
    }

    public function testThrowsWhenPermissionNameAlreadyExists(): void
    {
        $permissions = $this->createMock(PermissionRepositoryInterface::class);
        $permissions->method('findByName')->willReturn(new Permission());
        $permissions->expects(self::never())->method('save');

        $handler = new CreatePermissionHandler($permissions);

        $this->expectException(PermissionAlreadyExistsException::class);

        $handler->handle(new CreatePermissionCommand('orders.view', null));
    }
}
