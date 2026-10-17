<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Query;

use App\Application\Auth\Query\AuthReadModelInterface;
use App\Application\Auth\Query\ListPermissionsHandler;
use App\Application\Auth\Query\ListPermissionsQuery;
use App\Domain\Auth\Entity\Permission;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class ListPermissionsHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsPagedPermissions(): void
    {
        $permission = new Permission();
        $permission->setName('orders.view');
        self::setId($permission, 'perm-1');

        $permissions = $this->createStub(AuthReadModelInterface::class);
        $permissions->method('findPermissionsPaginated')->willReturn([$permission]);
        $permissions->method('countPermissions')->willReturn(1);

        $handler = new ListPermissionsHandler($permissions);

        $response = $handler->handle(new ListPermissionsQuery(1, 20));

        self::assertSame(1, $response->page);
        self::assertSame(20, $response->size);
        self::assertSame(1, $response->totalElements);
        self::assertSame(1, $response->totalPages);
        self::assertCount(1, $response->data);
        self::assertSame('orders.view', $response->data[0]->name);
    }

    public function testReturnsEmptyDataWhenNoPermissions(): void
    {
        $permissions = $this->createStub(AuthReadModelInterface::class);
        $permissions->method('findPermissionsPaginated')->willReturn([]);
        $permissions->method('countPermissions')->willReturn(0);

        $handler = new ListPermissionsHandler($permissions);

        self::assertSame([], $handler->handle(new ListPermissionsQuery(1, 20))->data);
    }
}
