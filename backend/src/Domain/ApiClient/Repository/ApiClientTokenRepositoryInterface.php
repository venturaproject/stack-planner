<?php

declare(strict_types=1);

namespace App\Domain\ApiClient\Repository;

use App\Domain\ApiClient\Entity\ApiClient;
use App\Domain\ApiClient\Entity\ApiClientToken;

interface ApiClientTokenRepositoryInterface
{
    public function findByAccessTokenHash(string $hash): ?ApiClientToken;

    public function findByRefreshTokenHash(string $hash): ?ApiClientToken;

    public function save(ApiClientToken $token): ApiClientToken;

    public function deleteByApiClient(ApiClient $apiClient): void;

    public function deleteByRefreshTokenHash(string $hash): void;

    public function deleteExpired(): void;
}
