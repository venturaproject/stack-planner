<?php

declare(strict_types=1);

namespace App\Domain\Issue\Entity;

final class IssueChecklistItem
{
    private ?int $id = null;

    public function __construct(
        private int $issueId,
        private string $text,
        private bool $isDone,
        private int $position,
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

    public function getText(): string
    {
        return $this->text;
    }

    public function isDone(): bool
    {
        return $this->isDone;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
