<?php

declare(strict_types=1);

namespace App\Domain\Issue\Entity;

use DateTimeImmutable;

final class IssueActivity
{
    private ?int $id = null;

    public function __construct(
        private int $issueId,
        private string $actorName,
        private string $field,
        private ?string $oldValue,
        private string $newValue,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIssueId(): int
    {
        return $this->issueId;
    }

    public function getActorName(): string
    {
        return $this->actorName;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getOldValue(): ?string
    {
        return $this->oldValue;
    }

    public function getNewValue(): string
    {
        return $this->newValue;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
