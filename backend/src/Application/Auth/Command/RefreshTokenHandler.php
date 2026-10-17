<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\AuthResponse;
use App\Application\Auth\DTO\UserResponse;
use App\Application\Auth\Exception\InvalidCredentialsException;
use App\Application\Auth\Security\AccessTokenIssuerInterface;
use App\Domain\Auth\Entity\RefreshToken;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class RefreshTokenHandler
{
    public function __construct(
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
        private readonly AccessTokenIssuerInterface $tokens,
    ) {
    }

    public function handle(RefreshTokenCommand $cmd): LoginResult
    {
        $token = $this->refreshTokens->findByToken(hash('sha256', $cmd->tokenValue));

        if (null === $token || !$token->isValid()) {
            throw new InvalidCredentialsException('Invalid or expired refresh token');
        }

        $user = $token->getUser();
        $accessToken = $this->tokens->issue($user);

        $newValue = bin2hex(random_bytes(40));
        // Carry the ORIGINAL token's expiry forward unchanged instead of computing
        // a fresh one. The frontend calls /refresh silently and automatically
        // (whenever the access token expires, ~every 15 min of activity), so
        // resetting the clock here meant the session never actually died as long
        // as it was used at least once within every refreshTtlSecs window — a
        // sliding session, not an expiring one, regardless of how far in the past
        // the original login was. This makes expiresAt an absolute cap from
        // login instead: rotation still happens every refresh (a stolen old
        // refresh token value stops working immediately, via revoke() below),
        // but the deadline itself never moves.
        $expiresAt = $token->getExpiresAt();

        $token->revoke();
        $this->refreshTokens->save(new RefreshToken(hash('sha256', $newValue), $user, $expiresAt));

        return new LoginResult(
            accessToken: $accessToken,
            refreshToken: $newValue,
            refreshExpires: $expiresAt,
            auth: new AuthResponse(accessToken: $accessToken, user: UserResponse::from($user)),
        );
    }
}
