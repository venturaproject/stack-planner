<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\ResetPasswordCommand;
use App\Application\Auth\Command\ResetPasswordHandler;
use App\Application\Auth\Security\PasswordHasherInterface;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Event\UserPasswordChanged;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use LogicException;
use PHPUnit\Framework\TestCase;

final class ResetPasswordHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function user(): User
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('old-hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        self::setId($user, 'user-1');

        return $user;
    }

    public function testResetsThePasswordAndInvalidatesTheLink(): void
    {
        $user = $this->user();
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects(self::once())->method('findById')->with('user-1')->willReturn($user);
        $users->expects(self::once())->method('save')->with($user);

        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects(self::once())->method('hash')->with($user, 'new-password')->willReturn('new-hash');
        $refreshTokens = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokens->expects(self::once())->method('revokeAllForUser')->with($user);

        (new ResetPasswordHandler($users, $hasher, $refreshTokens))->handle(new ResetPasswordCommand('user-1', 0, 'new-password'));

        self::assertSame('new-hash', $user->getPasswordHash());
        self::assertSame(1, $user->getActivationVersion());
        self::assertCount(1, array_filter($user->pullEvents(), static fn ($e) => $e instanceof UserPasswordChanged));
    }

    public function testRejectsAnAlreadyUsedLink(): void
    {
        $user = $this->user();
        // Simulate the link having already been consumed once (version moved on).
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $users->expects(self::never())->method('save');

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturn('new-hash');

        $this->expectException(LogicException::class);

        (new ResetPasswordHandler($users, $hasher, $this->createStub(RefreshTokenRepositoryInterface::class)))->handle(new ResetPasswordCommand('user-1', 1, 'new-password'));
    }

    public function testThrowsWhenUserDoesNotExist(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn(null);

        $hasher = $this->createStub(PasswordHasherInterface::class);

        $this->expectException(UserNotFoundException::class);

        (new ResetPasswordHandler($users, $hasher, $this->createStub(RefreshTokenRepositoryInterface::class)))->handle(new ResetPasswordCommand('missing', 0, 'new-password'));
    }
}
