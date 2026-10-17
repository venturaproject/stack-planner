<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\SetUpTotpCommand;
use App\Application\Auth\Command\SetUpTotpHandler;
use App\Application\Shared\Totp\TotpServiceInterface;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class SetUpTotpHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testGeneratesAndStoresAPendingSecretAndReturnsTheProvisioningUri(): void
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        self::setId($user, 'user-1');

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects(self::once())->method('findById')->with('user-1')->willReturn($user);
        $users->expects(self::once())->method('save')->with($user)->willReturnArgument(0);

        $totp = $this->createMock(TotpServiceInterface::class);
        $totp->method('generateSecret')->willReturn('NEWSECRET');
        $totp->expects(self::once())->method('getProvisioningUri')->with('NEWSECRET', 'me@example.com')
            ->willReturn('otpauth://totp/me@example.com?secret=NEWSECRET');

        $handler = new SetUpTotpHandler($users, $totp);

        $result = $handler->handle(new SetUpTotpCommand('user-1'));

        self::assertSame('NEWSECRET', $result->secret);
        self::assertSame('otpauth://totp/me@example.com?secret=NEWSECRET', $result->provisioningUri);
        self::assertSame('NEWSECRET', $user->getTotpSecret());
        self::assertFalse($user->isTotpEnabled(), 'setup is pending, not confirmed yet');
    }

    public function testThrowsWhenUserDoesNotExist(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn(null);

        $handler = new SetUpTotpHandler($users, $this->createStub(TotpServiceInterface::class));

        $this->expectException(UserNotFoundException::class);

        $handler->handle(new SetUpTotpCommand('unknown-id'));
    }
}
