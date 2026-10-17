<?php

declare(strict_types=1);

namespace App\Domain\Auth\Entity;

use DateTimeImmutable;

class RefreshToken
{
    private ?string $id = null;
    private string $token;
    private User $user;
    private DateTimeImmutable $expiresAt;
    private bool $revoked = false;
    private DateTimeImmutable $createdAt;

    public function __construct(string $token, User $user, DateTimeImmutable $expiresAt)
    {
        $this->token = $token;
        $this->user = $user;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function revoke(): void
    {
        $this->revoked = true;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }

    public function isValid(): bool
    {
        return !$this->revoked && !$this->isExpired();
    }
}
