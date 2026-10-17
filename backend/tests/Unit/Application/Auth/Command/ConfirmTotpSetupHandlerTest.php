<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\ConfirmTotpSetupCommand;
use App\Application\Auth\Command\ConfirmTotpSetupHandler;
use App\Application\Shared\Totp\TotpServiceInterface;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Exception\InvalidTotpCodeException;
use App\Domain\Auth\Exception\TotpSetupNotInitiatedException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class ConfirmTotpSetupHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function userWithPendingSetup(): User
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        self::setId($user, 'user-1');
        $user->initiateTotpSetup('PENDINGSECRET');

        return $user;
    }

    public function testConfirmsSetupAndReturnsPlaintextBackupCodesExactlyOnce(): void
    {
        $user = $this->userWithPendingSetup();

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects(self::once())->method('findById')->with('user-1')->willReturn($user);
        $users->expects(self::once())->method('save')->with($user)->willReturnArgument(0);

        $totp = $this->createMock(TotpServiceInterface::class);
        $totp->expects(self::once())->method('verify')->with('PENDINGSECRET', '123456')->willReturn(true);
        $totp->method('generateBackupCodes')->willReturn(['CODE1', 'CODE2']);

        $handler = new ConfirmTotpSetupHandler($users, $totp);

        $result = $handler->handle(new ConfirmTotpSetupCommand('user-1', '123456'));

        self::assertSame(['CODE1', 'CODE2'], $result->backupCodes);
        self::assertTrue($user->isTotpEnabled());
        self::assertSame(
            [hash('sha256', 'CODE1'), hash('sha256', 'CODE2')],
            $user->getTotpBackupCodeHashes(),
            'only hashes are persisted, never the plaintext codes',
        );
    }

    public function testThrowsOnAnInvalidCodeAndDoesNotEnableTotp(): void
    {
        $user = $this->userWithPendingSetup();

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);

        $totp = $this->createStub(TotpServiceInterface::class);
        $totp->method('verify')->willReturn(false);

        $handler = new ConfirmTotpSetupHandler($users, $totp);

        $this->expectException(InvalidTotpCodeException::class);

        try {
            $handler->handle(new ConfirmTotpSetupCommand('user-1', '000000'));
        } finally {
            self::assertFalse($user->isTotpEnabled());
        }
    }

    public function testThrowsWhenNoSetupWasEverInitiated(): void
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        self::setId($user, 'user-1');

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);

        $handler = new ConfirmTotpSetupHandler($users, $this->createStub(TotpServiceInterface::class));

        $this->expectException(TotpSetupNotInitiatedException::class);

        $handler->handle(new ConfirmTotpSetupCommand('user-1', '123456'));
    }
}
