<?php

declare(strict_types=1);

namespace App\Domain\Issue\Entity;

final class IssueAttachment
{
    private ?int $id = null;

    public function __construct(
        private int $issueId,
        private string $filename,
        private string $originalName,
        private string $mimeType,
        private int $sizeBytes,
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

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }
}
