<?php

declare(strict_types=1);

namespace App\Domain\Issue\Entity;

use DateTimeImmutable;

final class IssueComment
{
    private ?int $id = null;

    public function __construct(
        private int $issueId,
        private string $authorName,
        private string $content,
        private DateTimeImmutable $createdAt = new DateTimeImmutable(),
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
