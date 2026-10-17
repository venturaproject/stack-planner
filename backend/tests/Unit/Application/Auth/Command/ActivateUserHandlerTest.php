<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\ActivateUserCommand;
use App\Application\Auth\Command\ActivateUserHandler;
use App\Application\Auth\Security\PasswordHasherInterface;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class ActivateUserHandlerTest extends TestCase
{
    public function testActivatesUserWithThePasswordChosenByItsOwner(): void
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        $user->setPasswordHash('temporary-hash');
        $user->setActive(false);

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects(self::once())->method('findById')->with('user-1')->willReturn($user);
        $users->expects(self::once())->method('save')->with($user)->willReturn($user);
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects(self::once())->method('hash')->with($user, 'new-password')->willReturn('new-hash');

        (new ActivateUserHandler($users, $hasher))->handle(new ActivateUserCommand('user-1', 0, 'new-password'));

        self::assertTrue($user->isActive());
        self::assertSame('new-hash', $user->getPasswordHash());
    }
}
