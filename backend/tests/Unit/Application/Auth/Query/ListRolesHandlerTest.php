<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Query;

use App\Application\Auth\Query\AuthReadModelInterface;
use App\Application\Auth\Query\ListRolesHandler;
use App\Application\Auth\Query\ListRolesQuery;
use App\Domain\Auth\Entity\Role;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class ListRolesHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsPagedRoles(): void
    {
        $role = new Role();
        $role->setName('editor');
        self::setId($role, 'role-1');

        $roles = $this->createStub(AuthReadModelInterface::class);
        $roles->method('findRolesPaginated')->willReturn([$role]);
        $roles->method('countRoles')->willReturn(1);

        $handler = new ListRolesHandler($roles);

        $response = $handler->handle(new ListRolesQuery(1, 20));

        self::assertSame(1, $response->page);
        self::assertSame(20, $response->size);
        self::assertSame(1, $response->totalElements);
        self::assertSame(1, $response->totalPages);
        self::assertCount(1, $response->data);
        self::assertSame('editor', $response->data[0]->name);
    }

    public function testComputesTotalPagesFromTotalAndSize(): void
    {
        $roles = $this->createStub(AuthReadModelInterface::class);
        $roles->method('findRolesPaginated')->willReturn([]);
        $roles->method('countRoles')->willReturn(45);

        $handler = new ListRolesHandler($roles);

        $response = $handler->handle(new ListRolesQuery(2, 20));

        self::assertSame(3, $response->totalPages);
    }
}
