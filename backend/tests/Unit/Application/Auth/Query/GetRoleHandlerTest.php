<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Query;

use App\Application\Auth\Query\AuthReadModelInterface;
use App\Application\Auth\Query\GetRoleHandler;
use App\Application\Auth\Query\GetRoleQuery;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Exception\RoleNotFoundException;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class GetRoleHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsRoleById(): void
    {
        $role = new Role();
        $role->setName('editor');
        self::setId($role, 'role-1');

        $roles = $this->createStub(AuthReadModelInterface::class);
        $roles->method('findRoleById')->willReturn($role);

        $handler = new GetRoleHandler($roles);

        $response = $handler->handle(new GetRoleQuery('role-1'));

        self::assertSame('role-1', $response->id);
        self::assertSame('editor', $response->name);
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $roles = $this->createStub(AuthReadModelInterface::class);
        $roles->method('findRoleById')->willReturn(null);

        $handler = new GetRoleHandler($roles);

        $this->expectException(RoleNotFoundException::class);

        $handler->handle(new GetRoleQuery('missing'));
    }
}
