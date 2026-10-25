<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\AuthResponse;
use App\Application\Auth\DTO\UserResponse;
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
use DateTimeImmutable;

final class LoginHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
        private readonly AccessTokenIssuerInterface $tokens,
        private readonly PasswordHasherInterface $hasher,
        private readonly DomainEventPublisherInterface $events,
        private readonly TotpServiceInterface $totp,
        private readonly SecuritySettingsRepositoryInterface $securitySettings,
        /**
         * The session's absolute lifetime from this login, in seconds
         * (JWT_REFRESH_TTL_SECS, bound in config/services/auth.php). Every
         * /refresh rotates the token value but carries this same deadline
         * forward unchanged — see RefreshTokenHandler.
         */
        private readonly int $refreshTtlSecs = 604800,
    ) {
    }

    public function handle(LoginCommand $cmd): LoginResult
    {
        $user = $this->users->findByEmail($cmd->email)
            ?? throw new InvalidCredentialsException();

        if (!$this->hasher->verify($user, $cmd->password) || !$user->isActive()) {
            throw new InvalidCredentialsException();
        }

        $extraClaims = [];

        if ($user->isTotpEnabled()) {
            $this->verifySecondFactor($user, $cmd);
        } elseif (($this->securitySettings->findSingleton() ?? new SecuritySettings())->isRequire2fa()) {
            // Real session, but scoped down to the TOTP setup endpoints only — see
            // Require2faEnforcementListener — until they complete setup.
            $extraClaims['must_setup_totp'] = true;
        }

        $accessToken = $this->tokens->issue($user, $extraClaims);
        $refreshValue = bin2hex(random_bytes(40));
        $expiresAt = new DateTimeImmutable("+{$this->refreshTtlSecs} seconds");

        $this->refreshTokens->save(new RefreshToken(hash('sha256', $refreshValue), $user, $expiresAt));

        $this->events->publish(new UserLoggedIn($user->requireId(), $user->getEmail()));

        return new LoginResult(
            accessToken: $accessToken,
            refreshToken: $refreshValue,
            refreshExpires: $expiresAt,
            auth: new AuthResponse(accessToken: $accessToken, user: UserResponse::from($user)),
        );
    }

    private function verifySecondFactor(User $user, LoginCommand $cmd): void
    {
        if (null === $cmd->totpCode && null === $cmd->backupCode) {
            throw new TotpRequiredException();
        }

        if (null !== $cmd->totpCode && $this->totp->verify($user->getTotpSecret() ?? '', $cmd->totpCode)) {
            return;
        }

        if (null !== $cmd->backupCode && $user->consumeBackupCode(hash('sha256', $cmd->backupCode))) {
            $this->users->save($user);

            return;
        }

        throw new InvalidCredentialsException();
    }
}
