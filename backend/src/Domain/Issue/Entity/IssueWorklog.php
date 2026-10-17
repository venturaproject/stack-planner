<?php

declare(strict_types=1);

namespace App\Domain\Issue\Entity;

use DateTimeImmutable;

final class IssueWorklog
{
    private ?int $id = null;

    public function __construct(
        private int $issueId,
        private string $authorName,
        private int $minutes,
        private string $description,
        private DateTimeImmutable $loggedAt,
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

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function getMinutes(): int
    {
        return $this->minutes;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLoggedAt(): DateTimeImmutable
    {
        return $this->loggedAt;
    }
}
