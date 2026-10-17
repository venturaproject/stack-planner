<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\LoginCommand;
use App\Application\Auth\Command\LoginHandler;
use App\Application\Auth\Exception\InvalidCredentialsException;
use App\Application\Auth\Security\AccessTokenIssuerInterface;
use App\Application\Auth\Security\PasswordHasherInterface;
use App\Application\Shared\Event\DomainEventPublisherInterface;
use App\Application\Shared\Totp\TotpServiceInterface;
use App\Domain\Auth\Entity\RefreshToken;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Event\UserLoggedIn;
use App\Domain\Auth\Exception\TotpRequiredException;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Settings\Entity\SecuritySettings;
use App\Domain\Settings\Repository\SecuritySettingsRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class LoginHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function activeUser(): User
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hashed');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        self::setId($user, 'user-1');

        return $user;
    }

    /** Policy off by default — most tests don't care about it. */
    private function securitySettings(bool $require2fa = false): SecuritySettingsRepositoryInterface
    {
        $settings = new SecuritySettings();
        $settings->setRequire2fa($require2fa);

        $repo = $this->createStub(SecuritySettingsRepositoryInterface::class);
        $repo->method('findSingleton')->willReturn($settings);

        return $repo;
    }

    private function handler(
        UserRepositoryInterface $users,
        PasswordHasherInterface $hasher,
        AccessTokenIssuerInterface $jwtManager,
        ?RefreshTokenRepositoryInterface $refreshTokens = null,
        ?DomainEventPublisherInterface $events = null,
        ?TotpServiceInterface $totp = null,
        ?SecuritySettingsRepositoryInterface $securitySettings = null,
    ): LoginHandler {
        return new LoginHandler(
            $users,
            $refreshTokens ?? $this->createStub(RefreshTokenRepositoryInterface::class),
            $jwtManager,
            $hasher,
            $events ?? $this->createStub(DomainEventPublisherInterface::class),
            $totp ?? $this->createStub(TotpServiceInterface::class),
            $securitySettings ?? $this->securitySettings(),
        );
    }

    public function testSuccessfulLoginIssuesTokensAndPublishesAnOutboxEvent(): void
    {
        $user = $this->activeUser();

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(true);

        $jwtManager = $this->createStub(AccessTokenIssuerInterface::class);
        $jwtManager->method('issue')->willReturn('signed-jwt');

        $refreshTokens = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokens->expects(self::once())->method('save')
            ->with(self::isInstanceOf(RefreshToken::class))
            ->willReturnArgument(0);

        $events = $this->createMock(DomainEventPublisherInterface::class);
        $events->expects(self::once())->method('publish')
            ->with(self::isInstanceOf(UserLoggedIn::class));

        $handler = $this->handler($users, $hasher, $jwtManager, $refreshTokens, $events);

        $result = $handler->handle(new LoginCommand('me@example.com', 'plain-password'));

        self::assertSame('signed-jwt', $result->accessToken);
        self::assertNotEmpty($result->refreshToken);
        self::assertSame('me@example.com', $result->auth->user->email);
    }

    public function testThrowsOnUnknownEmail(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn(null);

        $handler = $this->handler(
            $users,
            $this->createStub(PasswordHasherInterface::class),
            $this->createStub(AccessTokenIssuerInterface::class),
        );

        $this->expectException(InvalidCredentialsException::class);

        $handler->handle(new LoginCommand('nobody@example.com', 'whatever'));
    }

    public function testThrowsOnWrongPassword(): void
    {
        $user = $this->activeUser();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(false);

        $handler = $this->handler($users, $hasher, $this->createStub(AccessTokenIssuerInterface::class));

        $this->expectException(InvalidCredentialsException::class);

        $handler->handle(new LoginCommand('me@example.com', 'wrong-password'));
    }

    public function testThrowsWhenUserIsInactive(): void
    {
        $user = $this->activeUser();
        $user->setActive(false);
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(true);

        $handler = $this->handler($users, $hasher, $this->createStub(AccessTokenIssuerInterface::class));

        $this->expectException(InvalidCredentialsException::class);

        $handler->handle(new LoginCommand('me@example.com', 'plain-password'));
    }

    // ── Two-factor ──────────────────────────────────────────────────────────

    private function userWithTotpEnabled(): User
    {
        $user = $this->activeUser();
        $user->initiateTotpSetup('SECRET');
        $user->confirmTotpSetup([hash('sha256', 'BACKUP-1')]);

        return $user;
    }

    public function testThrowsTotpRequiredWhenEnabledAndNoCodeGiven(): void
    {
        $user = $this->userWithTotpEnabled();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(true);

        $handler = $this->handler($users, $hasher, $this->createStub(AccessTokenIssuerInterface::class));

        $this->expectException(TotpRequiredException::class);

        $handler->handle(new LoginCommand('me@example.com', 'plain-password'));
    }

    public function testThrowsBadCredentialsWhenTotpCodeIsWrong(): void
    {
        $user = $this->userWithTotpEnabled();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(true);

        $totp = $this->createStub(TotpServiceInterface::class);
        $totp->method('verify')->willReturn(false);

        $handler = $this->handler($users, $hasher, $this->createStub(AccessTokenIssuerInterface::class), totp: $totp);

        $this->expectException(InvalidCredentialsException::class);

        $handler->handle(new LoginCommand('me@example.com', 'plain-password', totpCode: '000000'));
    }

    public function testSucceedsWithAValidTotpCode(): void
    {
        $user = $this->userWithTotpEnabled();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(true);

        $totp = $this->createStub(TotpServiceInterface::class);
        $totp->method('verify')->willReturn(true);

        $jwtManager = $this->createStub(AccessTokenIssuerInterface::class);
        $jwtManager->method('issue')->willReturn('signed-jwt');

        $handler = $this->handler($users, $hasher, $jwtManager, totp: $totp);

        $result = $handler->handle(new LoginCommand('me@example.com', 'plain-password', totpCode: '123456'));

        self::assertSame('signed-jwt', $result->accessToken);
    }

    public function testSucceedsWithAValidBackupCodeAndConsumesIt(): void
    {
        $user = $this->userWithTotpEnabled();
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);
        $users->expects(self::once())->method('save')->with($user)->willReturnArgument(0);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(true);

        $jwtManager = $this->createStub(AccessTokenIssuerInterface::class);
        $jwtManager->method('issue')->willReturn('signed-jwt');

        $handler = $this->handler($users, $hasher, $jwtManager);

        $result = $handler->handle(new LoginCommand('me@example.com', 'plain-password', backupCode: 'BACKUP-1'));

        self::assertSame('signed-jwt', $result->accessToken);
        self::assertSame([], $user->getTotpBackupCodeHashes(), 'the backup code should be single-use');
    }

    public function testThrowsBadCredentialsWhenBackupCodeIsWrong(): void
    {
        $user = $this->userWithTotpEnabled();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(true);

        $handler = $this->handler($users, $hasher, $this->createStub(AccessTokenIssuerInterface::class));

        $this->expectException(InvalidCredentialsException::class);

        $handler->handle(new LoginCommand('me@example.com', 'plain-password', backupCode: 'NOT-A-REAL-CODE'));
    }

    public function testIssuesAMustSetupTotpClaimWhenPolicyRequiresItAndUserHasNotEnabledIt(): void
    {
        $user = $this->activeUser();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(true);

        $jwtManager = $this->createMock(AccessTokenIssuerInterface::class);
        $jwtManager->expects(self::once())->method('issue')
            ->with($user, ['must_setup_totp' => true])
            ->willReturn('scoped-jwt');

        $handler = $this->handler(
            $users,
            $hasher,
            $jwtManager,
            securitySettings: $this->securitySettings(require2fa: true),
        );

        $result = $handler->handle(new LoginCommand('me@example.com', 'plain-password'));

        self::assertSame('scoped-jwt', $result->accessToken);
    }

    public function testDoesNotIssueAMustSetupTotpClaimWhenPolicyIsOffEvenWithoutTotpEnabled(): void
    {
        $user = $this->activeUser();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);

        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('verify')->willReturn(true);

        $jwtManager = $this->createMock(AccessTokenIssuerInterface::class);
        $jwtManager->expects(self::once())->method('issue')->willReturn('normal-jwt');

        $handler = $this->handler($users, $hasher, $jwtManager, securitySettings: $this->securitySettings(require2fa: false));

        $result = $handler->handle(new LoginCommand('me@example.com', 'plain-password'));

        self::assertSame('normal-jwt', $result->accessToken);
    }
}
