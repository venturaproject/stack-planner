<?php

declare(strict_types=1);

namespace App\Domain\Project\Entity;

final class ProjectMember
{
    private ?int $id = null;

    public function __construct(
        private int $projectId,
        private string $userId,
        private string $role,
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

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
