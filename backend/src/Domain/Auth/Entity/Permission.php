<?php

declare(strict_types=1);

namespace App\Domain\Auth\Entity;

use DateTimeImmutable;
use LogicException;

class Permission
{
    private ?string $id = null;
    private string $name;
    private ?string $description = null;
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function requireId(): string
    {
        if (null === $this->id) {
            throw new LogicException('A persisted permission is required.');
        }

        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
