<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\LogoutCommand;
use App\Application\Auth\Command\LogoutHandler;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class LogoutHandlerTest extends TestCase
{
    public function testRevokesAllRefreshTokensForUser(): void
    {
        $user = new User();
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects(self::once())->method('findById')->with('user-1')->willReturn($user);

        $refreshTokens = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokens->expects(self::once())->method('revokeAllForUser')->with($user);

        $handler = new LogoutHandler($refreshTokens, $users);

        $handler->handle(new LogoutCommand('user-1'));
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn(null);

        $refreshTokens = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokens->expects(self::never())->method('revokeAllForUser');

        $handler = new LogoutHandler($refreshTokens, $users);

        $this->expectException(UserNotFoundException::class);

        $handler->handle(new LogoutCommand('missing'));
    }
}
