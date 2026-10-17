<?php

declare(strict_types=1);

namespace App\Domain\Project\Entity;

use DateTimeImmutable;
use InvalidArgumentException;

final class Project
{
    private ?int $id = null;
    private DateTimeImmutable $createdAt;

    private function __construct(
        private string $name,
        private string $projectKey,
        private string $description,
        private string $status,
        private string $coverImage,
        private string $color,
        private ?DateTimeImmutable $startDate,
        private ?DateTimeImmutable $endDate,
    ) {
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(string $name, string $projectKey, string $description, string $coverImage, string $color = '#6366f1', ?DateTimeImmutable $startDate = null, ?DateTimeImmutable $endDate = null): self
    {
        $name = trim($name);
        $projectKey = strtoupper(trim($projectKey));
        if ('' === $name || !preg_match('/^[A-Z][A-Z0-9]{1,7}$/', $projectKey)) {
            throw new InvalidArgumentException('Project name and a 2–8 character key are required.');
        }

        return new self($name, $projectKey, trim($description), 'active', $coverImage, $color, $startDate, $endDate);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProjectKey(): string
    {
        return $this->projectKey;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCoverImage(): string
    {
        return $this->coverImage;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getStartDate(): ?DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
