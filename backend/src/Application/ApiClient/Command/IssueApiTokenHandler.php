<?php

declare(strict_types=1);

namespace App\Application\ApiClient\Command;

use App\Application\ApiClient\DTO\ApiTokenResponse;
use App\Application\Auth\Exception\InvalidCredentialsException;
use App\Domain\ApiClient\Entity\ApiClientToken;
use App\Domain\ApiClient\Repository\ApiClientRepositoryInterface;
use App\Domain\ApiClient\Repository\ApiClientTokenRepositoryInterface;
use DateTimeImmutable;

final class IssueApiTokenHandler
{
    private const int ACCESS_TTL_SECS = 3600;
    private const int REFRESH_TTL_SECS = 2592000;

    public function __construct(
        private readonly ApiClientRepositoryInterface $clients,
        private readonly ApiClientTokenRepositoryInterface $tokens,
    ) {
    }

    public function handle(IssueApiTokenCommand $cmd): ApiTokenResponse
    {
        $client = $this->clients->findByClientId($cmd->clientId);

        if (null === $client || !$client->isActive() || !$client->verifySecret($cmd->clientSecret)) {
            throw new InvalidCredentialsException('Invalid client credentials');
        }

        $this->tokens->deleteByApiClient($client);

        $rawAccess = bin2hex(random_bytes(40));
        $rawRefresh = bin2hex(random_bytes(40));

        $this->tokens->save(new ApiClientToken(
            apiClient: $client,
            accessTokenHash: hash('sha256', $rawAccess),
            refreshTokenHash: hash('sha256', $rawRefresh),
            scopes: $client->getScopes(),
            accessExpiresAt: new DateTimeImmutable('+'.self::ACCESS_TTL_SECS.' seconds'),
            refreshExpiresAt: new DateTimeImmutable('+'.self::REFRESH_TTL_SECS.' seconds'),
        ));

        $this->clients->updateLastUsed($client->getClientId());

        return new ApiTokenResponse(
            accessToken: $rawAccess,
            refreshToken: $rawRefresh,
            tokenType: 'Bearer',
            expiresIn: self::ACCESS_TTL_SECS,
            scopes: $client->getScopes(),
        );
    }
}
