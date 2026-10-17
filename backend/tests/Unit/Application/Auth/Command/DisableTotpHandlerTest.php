<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\DisableTotpCommand;
use App\Application\Auth\Command\DisableTotpHandler;
use App\Application\Auth\Exception\InvalidCredentialsException;
use App\Application\Auth\Security\PasswordHasherInterface;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Exception\TotpRequiredByPolicyException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Settings\Entity\SecuritySettings;
use App\Domain\Settings\Repository\SecuritySettingsRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class DisableTotpHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function userWithTotpEnabled(): User
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        self::setId($user, 'user-1');
        $user->initiateTotpSetup('SECRET');
        $user->confirmTotpSetup(['hash1']);

        return $user;
    }

    private function securitySettings(bool $require2fa): SecuritySettingsRepositoryInterface
    {
        $settings = new SecuritySettings();
        $settings->setRequire2fa($require2fa);

        $repo = $this->createStub(SecuritySettingsRepositoryInterface::class);
        $repo->method('findSingleton')->willReturn($settings);

        return $repo;
    }

    public function testDisablesTotpWithACorrectPassword(): void
    {
        $user = $this->userWithTotpEnabled();

        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects(self::once())->method('findById')->with('user-1')->willReturn($user);
        $users->expects(self::once())->method('save')->with($user)->willReturnArgument(0);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(true);

        $handler = new DisableTotpHandler($users, $hasher, $this->securitySettings(false));

        $handler->handle(new DisableTotpCommand('user-1', 'correct-password'));

        self::assertFalse($user->isTotpEnabled());
    }

    public function testThrowsOnAWrongPassword(): void
    {
        $user = $this->userWithTotpEnabled();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(false);

        $handler = new DisableTotpHandler($users, $hasher, $this->securitySettings(false));

        $this->expectException(InvalidCredentialsException::class);

        $handler->handle(new DisableTotpCommand('user-1', 'wrong-password'));
    }

    public function testCannotDisableWhilePolicyRequiresIt(): void
    {
        $user = $this->userWithTotpEnabled();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(true);

        $handler = new DisableTotpHandler($users, $hasher, $this->securitySettings(true));

        $this->expectException(TotpRequiredByPolicyException::class);

        try {
            $handler->handle(new DisableTotpCommand('user-1', 'correct-password'));
        } finally {
            self::assertTrue($user->isTotpEnabled(), 'must stay enabled');
        }
    }
}
