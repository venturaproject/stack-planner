<?php

declare(strict_types=1);

namespace App\Domain\Planning\Entity;

final class Epic
{
    private ?int $id = null;

    public function __construct(
        private int $projectId,
        private string $epicKey,
        private string $name,
        private string $description,
        private string $status,
        private int $progress,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getEpicKey(): string
    {
        return $this->epicKey;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }
}
