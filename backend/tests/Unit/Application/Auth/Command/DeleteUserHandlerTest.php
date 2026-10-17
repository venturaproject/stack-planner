<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\DeleteUserCommand;
use App\Application\Auth\Command\DeleteUserHandler;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteUserHandlerTest extends TestCase
{
    public function testDeletesExistingUser(): void
    {
        $user = new User();
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects(self::once())->method('findById')->with('user-1')->willReturn($user);
        $users->expects(self::once())->method('delete')->with($user);

        $handler = new DeleteUserHandler($users);

        $handler->handle(new DeleteUserCommand('user-1'));
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findById')->willReturn(null);
        $users->expects(self::never())->method('delete');

        $handler = new DeleteUserHandler($users);

        $this->expectException(UserNotFoundException::class);

        $handler->handle(new DeleteUserCommand('missing'));
    }
}
