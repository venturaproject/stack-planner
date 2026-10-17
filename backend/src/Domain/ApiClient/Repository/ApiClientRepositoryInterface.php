<?php

declare(strict_types=1);

namespace App\Domain\ApiClient\Repository;

use App\Domain\ApiClient\Entity\ApiClient;

interface ApiClientRepositoryInterface
{
    public function findById(string $id): ?ApiClient;

    public function findByClientId(string $clientId): ?ApiClient;

    public function save(ApiClient $client): ApiClient;

    public function updateLastUsed(string $clientId): void;
}
