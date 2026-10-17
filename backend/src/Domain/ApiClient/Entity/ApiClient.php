<?php

declare(strict_types=1);

namespace App\Domain\ApiClient\Entity;

use DateTimeImmutable;

class ApiClient
{
    public const array KNOWN_SCOPES = [
        'customers:read',
        'customers:write',
        'products:read',
        'products:write',
        'orders:read',
        'orders:write',
        'dashboard:read',
        'projects:read',
        'tasks:read',
        'tasks:write',
    ];

    private ?string $id = null;
    private string $name;
    private string $clientId;
    private string $secretHash;

    /** @var string[] */
    private array $scopes;

    private bool $active = true;
    private ?DateTimeImmutable $lastUsedAt = null;
    private DateTimeImmutable $createdAt;

    /** @param string[] $scopes */
    public function __construct(string $name, string $clientId, string $secretHash, array $scopes)
    {
        $this->name = $name;
        $this->clientId = $clientId;
        $this->secretHash = $secretHash;
        $this->scopes = $scopes;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getSecretHash(): string
    {
        return $this->secretHash;
    }

    /** @return string[] */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function verifySecret(string $rawSecret): bool
    {
        return hash_equals($this->secretHash, hash('sha256', $rawSecret));
    }

    public function hasScope(string $scope): bool
    {
        return \in_array('*', $this->scopes, true) || \in_array($scope, $this->scopes, true);
    }

    public function touchLastUsed(): void
    {
        $this->lastUsedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->active = false;
    }
}
