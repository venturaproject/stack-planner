<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\UpdateUserCommand;
use App\Application\Auth\Command\UpdateUserHandler;
use App\Application\Auth\Security\PasswordHasherInterface;
use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Exception\PermissionNotFoundException;
use App\Domain\Auth\Exception\RoleNotFoundException;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class UpdateUserHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function handler(UserRepositoryInterface $users, RoleRepositoryInterface $roles, PermissionRepositoryInterface $permissions, PasswordHasherInterface $hasher): UpdateUserHandler
    {
        return new UpdateUserHandler($users, $roles, $permissions, $hasher, $this->createStub(RefreshTokenRepositoryInterface::class));
    }

    private function existingUser(): User
    {
        $user = new User();
        $user->setEmail('old@example.com');
        $user->setPasswordHash('old-hash');
        $user->setFirstName('Old');
        $user->setLastName('Name');
        self::setId($user, 'user-1');

        return $user;
    }

    public function testUpdatesBasicFields(): void
    {
        $user = $this->existingUser();

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $users->method('save')->willReturnArgument(0);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $permissions = $this->createStub(PermissionRepositoryInterface::class);
        $hasher = $this->createStub(PasswordHasherInterface::class);

        $cmd = new UpdateUserCommand();
        $cmd->id = 'user-1';
        $cmd->firstName = 'New';
        $cmd->lastName = 'Name';
        $cmd->active = false;

        $handler = $this->handler($users, $roles, $permissions, $hasher);
        $response = $handler->handle($cmd);

        self::assertSame('New', $response->firstName);
        self::assertFalse($response->active);
        self::assertSame('old@example.com', $response->email);
    }

    public function testChangesEmailAndPasswordWhenProvided(): void
    {
        $user = $this->existingUser();

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $users->method('save')->willReturnArgument(0);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $permissions = $this->createStub(PermissionRepositoryInterface::class);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects(self::once())->method('hash')->willReturn('new-hash');

        $cmd = new UpdateUserCommand();
        $cmd->id = 'user-1';
        $cmd->firstName = 'Old';
        $cmd->lastName = 'Name';
        $cmd->email = 'new@example.com';
        $cmd->password = 'new-password';

        $handler = $this->handler($users, $roles, $permissions, $hasher);
        $response = $handler->handle($cmd);

        self::assertSame('new@example.com', $response->email);
        self::assertSame('new-hash', $user->getPasswordHash());
    }

    public function testAssignsRolesAndPermissionsWhenProvided(): void
    {
        $user = $this->existingUser();
        $role = new Role();
        $role->setName('editor');
        self::setId($role, 'role-1');
        $permission = new Permission();
        $permission->setName('orders.view');
        self::setId($permission, 'perm-1');

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $users->method('save')->willReturnArgument(0);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findById')->willReturn($role);
        $permissions = $this->createStub(PermissionRepositoryInterface::class);
        $permissions->method('findById')->willReturn($permission);
        $hasher = $this->createStub(PasswordHasherInterface::class);

        $cmd = new UpdateUserCommand();
        $cmd->id = 'user-1';
        $cmd->firstName = 'Old';
        $cmd->lastName = 'Name';
        $cmd->roleIds = ['role-1'];
        $cmd->permissionIds = ['perm-1'];

        $handler = $this->handler($users, $roles, $permissions, $hasher);
        $response = $handler->handle($cmd);

        self::assertCount(1, $response->roles);
        self::assertSame('editor', $response->roles[0]->name);
        self::assertCount(1, $response->permissions);
        self::assertSame('orders.view', $response->permissions[0]->name);
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn(null);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $permissions = $this->createStub(PermissionRepositoryInterface::class);
        $hasher = $this->createStub(PasswordHasherInterface::class);

        $cmd = new UpdateUserCommand();
        $cmd->id = 'missing';

        $handler = $this->handler($users, $roles, $permissions, $hasher);

        $this->expectException(UserNotFoundException::class);

        $handler->handle($cmd);
    }

    public function testThrowsWhenRoleNotFound(): void
    {
        $user = $this->existingUser();

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $roles->method('findById')->willReturn(null);
        $permissions = $this->createStub(PermissionRepositoryInterface::class);
        $hasher = $this->createStub(PasswordHasherInterface::class);

        $cmd = new UpdateUserCommand();
        $cmd->id = 'user-1';
        $cmd->roleIds = ['missing-role'];

        $handler = $this->handler($users, $roles, $permissions, $hasher);

        $this->expectException(RoleNotFoundException::class);

        $handler->handle($cmd);
    }

    public function testThrowsWhenPermissionNotFound(): void
    {
        $user = $this->existingUser();

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $permissions = $this->createStub(PermissionRepositoryInterface::class);
        $permissions->method('findById')->willReturn(null);
        $hasher = $this->createStub(PasswordHasherInterface::class);

        $cmd = new UpdateUserCommand();
        $cmd->id = 'user-1';
        $cmd->permissionIds = ['missing-perm'];

        $handler = $this->handler($users, $roles, $permissions, $hasher);

        $this->expectException(PermissionNotFoundException::class);

        $handler->handle($cmd);
    }
}
