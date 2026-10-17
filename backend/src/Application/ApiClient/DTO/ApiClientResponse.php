<?php

declare(strict_types=1);

namespace App\Application\ApiClient\DTO;

use App\Domain\ApiClient\Entity\ApiClient;
use DateTimeInterface;

final readonly class ApiClientResponse
{
    /** @param string[] $scopes */
    public function __construct(
        public string $id,
        public string $name,
        public string $clientId,
        public array $scopes,
        public bool $active,
        public ?string $lastUsedAt,
        public string $createdAt,
    ) {
    }

    public static function from(ApiClient $client): self
    {
        return new self(
            id: (string) $client->getId(),
            name: $client->getName(),
            clientId: $client->getClientId(),
            scopes: $client->getScopes(),
            active: $client->isActive(),
            lastUsedAt: $client->getLastUsedAt()?->format(DateTimeInterface::ATOM),
            createdAt: $client->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
