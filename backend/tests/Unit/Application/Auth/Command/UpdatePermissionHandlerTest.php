<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\UpdatePermissionCommand;
use App\Application\Auth\Command\UpdatePermissionHandler;
use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Exception\PermissionNotFoundException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class UpdatePermissionHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testUpdatesNameAndDescription(): void
    {
        $permission = new Permission();
        $permission->setName('old-name');
        self::setId($permission, 'perm-1');

        $permissions = $this->createStub(PermissionRepositoryInterface::class);
        $permissions->method('findById')->willReturn($permission);
        $permissions->method('save')->willReturnArgument(0);

        $handler = new UpdatePermissionHandler($permissions);

        $response = $handler->handle(new UpdatePermissionCommand('perm-1', 'new-name', 'new description'));

        self::assertSame('new-name', $response->name);
        self::assertSame('new description', $response->description);
    }

    public function testThrowsWhenPermissionNotFound(): void
    {
        $permissions = $this->createStub(PermissionRepositoryInterface::class);
        $permissions->method('findById')->willReturn(null);

        $handler = new UpdatePermissionHandler($permissions);

        $this->expectException(PermissionNotFoundException::class);

        $handler->handle(new UpdatePermissionCommand('missing', 'name', null));
    }
}
