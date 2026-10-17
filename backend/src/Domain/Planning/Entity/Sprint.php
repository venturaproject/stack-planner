<?php

declare(strict_types=1);

namespace App\Domain\Planning\Entity;

use DateTimeImmutable;

final class Sprint
{
    private ?int $id = null;

    public function __construct(
        private int $projectId,
        private string $name,
        private string $goal,
        private string $status,
        private DateTimeImmutable $startAt,
        private DateTimeImmutable $endAt,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getGoal(): string
    {
        return $this->goal;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStartAt(): DateTimeImmutable
    {
        return $this->startAt;
    }

    public function getEndAt(): DateTimeImmutable
    {
        return $this->endAt;
    }
}
