<?php

declare(strict_types=1);

namespace App\Domain\Issue\Entity;

use DateTimeImmutable;
use InvalidArgumentException;

final class Issue
{
    private ?int $id = null;
    private DateTimeImmutable $createdAt;

    /** @param list<string> $assigneeNames */
    public function __construct(
        private int $projectId,
        private string $issueKey,
        private string $title,
        private string $status,
        private string $priority,
        private ?DateTimeImmutable $dueAt = null,
        private ?int $epicId = null,
        private ?int $sprintId = null,
        private string $type = 'task',
        private ?int $storyPoints = null,
        private string $reporterName = 'Admin User',
        private array $assigneeNames = [],
        ?DateTimeImmutable $createdAt = null,
        private ?string $description = null,
        private ?DateTimeImmutable $startAt = null,
    ) {
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        if ('' === trim($title)) {
            throw new InvalidArgumentException('Issue title is required.');
        }
        if (!\in_array($status, ['todo', 'in_progress', 'in_review', 'done'], true)) {
            throw new InvalidArgumentException('Unknown issue status.');
        }
        if (!\in_array($priority, ['low', 'medium', 'high', 'urgent', 'critical'], true)) {
            throw new InvalidArgumentException('Unknown issue priority.');
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getIssueKey(): string
    {
        return $this->issueKey;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getDueAt(): ?DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function getStartAt(): ?DateTimeImmutable
    {
        return $this->startAt;
    }

    public function getEpicId(): ?int
    {
        return $this->epicId;
    }

    public function getSprintId(): ?int
    {
        return $this->sprintId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStoryPoints(): ?int
    {
        return $this->storyPoints;
    }

    public function getReporterName(): string
    {
        return $this->reporterName;
    }

    /** @return list<string> */
    public function getAssigneeNames(): array
    {
        return $this->assigneeNames;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /** @param list<string> $assigneeNames */
    public function updateDetails(string $title, string $type, string $priority, ?string $description, ?DateTimeImmutable $dueAt, ?int $epicId, ?int $sprintId, ?int $storyPoints, array $assigneeNames): void
    {
        if ('' === trim($title)) {
            throw new InvalidArgumentException('Issue title is required.');
        }
        if (!\in_array($type, ['story', 'task', 'bug', 'subtask'], true)) {
            throw new InvalidArgumentException('Unknown issue type.');
        }
        if (!\in_array($priority, ['low', 'medium', 'high', 'urgent', 'critical'], true)) {
            throw new InvalidArgumentException('Unknown issue priority.');
        }

        $this->title = trim($title);
        $this->type = $type;
        $this->priority = $priority;
        $this->description = $description;
        $this->dueAt = $dueAt;
        $this->epicId = $epicId;
        $this->sprintId = $sprintId;
        $this->storyPoints = $storyPoints;
        $this->assigneeNames = $assigneeNames;
    }

    public function changeStatus(string $status): void
    {
        if (!\in_array($status, ['todo', 'in_progress', 'in_review', 'done'], true)) {
            throw new InvalidArgumentException('Unknown issue status.');
        }

        $this->status = $status;
    }
}
