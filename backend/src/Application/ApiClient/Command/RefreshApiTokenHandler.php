<?php

declare(strict_types=1);

namespace App\Application\ApiClient\Command;

use App\Application\ApiClient\DTO\ApiTokenResponse;
use App\Application\Auth\Exception\InvalidCredentialsException;
use App\Domain\ApiClient\Entity\ApiClientToken;
use App\Domain\ApiClient\Repository\ApiClientRepositoryInterface;
use App\Domain\ApiClient\Repository\ApiClientTokenRepositoryInterface;
use DateTimeImmutable;

final class RefreshApiTokenHandler
{
    private const int ACCESS_TTL_SECS = 3600;
    private const int REFRESH_TTL_SECS = 2592000;

    public function __construct(
        private readonly ApiClientRepositoryInterface $clients,
        private readonly ApiClientTokenRepositoryInterface $tokens,
    ) {
    }

    public function handle(RefreshApiTokenCommand $cmd): ApiTokenResponse
    {
        $hash = hash('sha256', $cmd->refreshToken);
        $existing = $this->tokens->findByRefreshTokenHash($hash);

        if (null === $existing || $existing->isRefreshExpired()) {
            throw new InvalidCredentialsException('Invalid or expired refresh token');
        }

        $client = $existing->getApiClient();

        if (!$client->isActive()) {
            throw new InvalidCredentialsException('Client not found or inactive');
        }

        $this->tokens->deleteByRefreshTokenHash($hash);

        $rawAccess = bin2hex(random_bytes(40));
        $rawRefresh = bin2hex(random_bytes(40));

        $this->tokens->save(new ApiClientToken(
            apiClient: $client,
            accessTokenHash: hash('sha256', $rawAccess),
            refreshTokenHash: hash('sha256', $rawRefresh),
            scopes: $existing->getScopes(),
            accessExpiresAt: new DateTimeImmutable('+'.self::ACCESS_TTL_SECS.' seconds'),
            refreshExpiresAt: new DateTimeImmutable('+'.self::REFRESH_TTL_SECS.' seconds'),
        ));

        $this->clients->updateLastUsed($client->getClientId());

        return new ApiTokenResponse(
            accessToken: $rawAccess,
            refreshToken: $rawRefresh,
            tokenType: 'Bearer',
            expiresIn: self::ACCESS_TTL_SECS,
            scopes: $existing->getScopes(),
        );
    }
}
