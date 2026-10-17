<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\CreateUserCommand;
use App\Application\Auth\Command\CreateUserHandler;
use App\Application\Auth\Security\PasswordHasherInterface;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Exception\EmailAlreadyInUseException;
use App\Domain\Auth\Exception\RoleNotFoundException;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class CreateUserHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testCreatesUserWithHashedPassword(): void
    {
        $users = $this->createMock(UserRepositoryInterface::class);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $hasher = $this->createStub(PasswordHasherInterface::class);

        $users->expects(self::once())->method('existsByEmail')->with('new@example.com')->willReturn(false);
        $hasher->method('hash')->willReturn('hashed-password');
        $users->expects(self::once())->method('save')->willReturnCallback(static function (User $user) {
            self::setId($user, 'user-1');

            return $user;
        });

        $handler = new CreateUserHandler($users, $roles, $hasher);

        $response = $handler->handle(new CreateUserCommand(
            email: 'new@example.com',
            firstName: 'Ada',
            lastName: 'Lovelace',
        ));

        self::assertSame('new@example.com', $response->email);
        self::assertSame('Ada', $response->firstName);
        self::assertFalse($response->active);
    }

    public function testThrowsWhenEmailAlreadyInUse(): void
    {
        $users = $this->createMock(UserRepositoryInterface::class);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $hasher = $this->createStub(PasswordHasherInterface::class);

        $users->method('existsByEmail')->willReturn(true);
        $users->expects(self::never())->method('save');

        $handler = new CreateUserHandler($users, $roles, $hasher);

        $this->expectException(EmailAlreadyInUseException::class);

        $handler->handle(new CreateUserCommand('taken@example.com', 'Ada', 'Lovelace'));
    }

    public function testAssignsRolesWhenProvided(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $roles = $this->createMock(RoleRepositoryInterface::class);
        $hasher = $this->createStub(PasswordHasherInterface::class);

        $role = new Role();
        $role->setName('admin');
        self::setId($role, 'role-1');

        $users->method('existsByEmail')->willReturn(false);
        $hasher->method('hash')->willReturn('hashed');
        $roles->expects(self::once())->method('findById')->with('role-1')->willReturn($role);
        $users->method('save')->willReturnCallback(static function (User $user) {
            self::setId($user, 'user-1');

            return $user;
        });

        $handler = new CreateUserHandler($users, $roles, $hasher);

        $response = $handler->handle(new CreateUserCommand(
            email: 'new@example.com',
            firstName: 'Ada',
            lastName: 'Lovelace',
            roleIds: ['role-1'],
        ));

        self::assertCount(1, $response->roles);
        self::assertSame('admin', $response->roles[0]->name);
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $hasher = $this->createStub(PasswordHasherInterface::class);

        $users->method('existsByEmail')->willReturn(false);
        $hasher->method('hash')->willReturn('hashed');
        $roles->method('findById')->willReturn(null);

        $handler = new CreateUserHandler($users, $roles, $hasher);

        $this->expectException(RoleNotFoundException::class);

        $handler->handle(new CreateUserCommand('new@example.com', 'Ada', 'Lovelace', ['missing-role']));
    }
}
