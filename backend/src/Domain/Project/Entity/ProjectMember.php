<?php

declare(strict_types=1);

namespace App\Domain\Project\Entity;

use InvalidArgumentException;

final class ProjectMember
{
    private ?int $id = null;
    private string $role;

    public function __construct(
        private int $projectId,
        private string $userId,
        string $role,
    ) {
        $this->changeRole($role);
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

    public function changeRole(string $role): void
    {
        if (!\in_array($role, ['owner', 'member'], true)) {
            throw new InvalidArgumentException('Project membership role must be owner or member.');
        }

        $this->role = $role;
    }
}
