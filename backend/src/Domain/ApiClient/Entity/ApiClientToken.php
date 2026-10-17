<?php

declare(strict_types=1);

namespace App\Domain\ApiClient\Entity;

use DateTimeImmutable;

class ApiClientToken
{
    private ?int $id = null;
    private ApiClient $apiClient;
    private string $accessTokenHash;
    private string $refreshTokenHash;

    /** @var string[] */
    private array $scopes;

    private DateTimeImmutable $accessExpiresAt;
    private DateTimeImmutable $refreshExpiresAt;
    private DateTimeImmutable $createdAt;

    /** @param string[] $scopes */
    public function __construct(
        ApiClient $apiClient,
        string $accessTokenHash,
        string $refreshTokenHash,
        array $scopes,
        DateTimeImmutable $accessExpiresAt,
        DateTimeImmutable $refreshExpiresAt,
    ) {
        $this->apiClient = $apiClient;
        $this->accessTokenHash = $accessTokenHash;
        $this->refreshTokenHash = $refreshTokenHash;
        $this->scopes = $scopes;
        $this->accessExpiresAt = $accessExpiresAt;
        $this->refreshExpiresAt = $refreshExpiresAt;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApiClient(): ApiClient
    {
        return $this->apiClient;
    }

    public function getAccessTokenHash(): string
    {
        return $this->accessTokenHash;
    }

    public function getRefreshTokenHash(): string
    {
        return $this->refreshTokenHash;
    }

    /** @return string[] */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getAccessExpiresAt(): DateTimeImmutable
    {
        return $this->accessExpiresAt;
    }

    public function getRefreshExpiresAt(): DateTimeImmutable
    {
        return $this->refreshExpiresAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isAccessExpired(): bool
    {
        return $this->accessExpiresAt < new DateTimeImmutable();
    }

    public function isRefreshExpired(): bool
    {
        return $this->refreshExpiresAt < new DateTimeImmutable();
    }
}
