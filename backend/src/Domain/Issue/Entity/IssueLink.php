<?php

declare(strict_types=1);

namespace App\Domain\Issue\Entity;

final class IssueLink
{
    private ?int $id = null;

    public function __construct(
        private int $sourceIssueId,
        private int $targetIssueId,
        private string $linkType,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceIssueId(): int
    {
        return $this->sourceIssueId;
    }

    public function getTargetIssueId(): int
    {
        return $this->targetIssueId;
    }

    public function getLinkType(): string
    {
        return $this->linkType;
    }
}
