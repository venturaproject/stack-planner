<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\AuthResponse;
use App\Application\Auth\DTO\UserResponse;
use App\Application\Auth\Security\AccessTokenIssuerInterface;
use App\Domain\Auth\Entity\RefreshToken;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Re-issues a normal (access + refresh token) session for the real admin identity
 * carried in the impersonation JWT's `impersonator_email` claim — no password check,
 * since that identity was already proven when the impersonation session started (see
 * ImpersonateUserHandler). Mirrors LoginHandler's token issuance, minus credentials.
 */
#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class StopImpersonatingHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
        private readonly AccessTokenIssuerInterface $tokens,
        private readonly int $refreshTtlSecs = 604800,
    ) {
    }

    public function handle(StopImpersonatingCommand $cmd): LoginResult
    {
        $admin = $this->users->findByEmail($cmd->impersonatorEmail)
            ?? throw new UserNotFoundException();

        $accessToken = $this->tokens->issue($admin);
        $refreshValue = bin2hex(random_bytes(40));
        $expiresAt = new DateTimeImmutable("+{$this->refreshTtlSecs} seconds");

        $this->refreshTokens->save(new RefreshToken(hash('sha256', $refreshValue), $admin, $expiresAt));

        return new LoginResult(
            accessToken: $accessToken,
            refreshToken: $refreshValue,
            refreshExpires: $expiresAt,
            auth: new AuthResponse(accessToken: $accessToken, user: UserResponse::from($admin)),
        );
    }
}
