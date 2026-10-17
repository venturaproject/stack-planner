<?php

declare(strict_types=1);

namespace App\Application\Issue\Command;

final readonly class CreateIssueCommand
{
    /** @param list<string> $assigneeNames */
    public function __construct(
        public int $projectId,
        public string $title,
        public string $priority = 'medium',
        public ?string $dueAt = null,
        public string $type = 'task',
        public ?int $storyPoints = null,
        public ?string $description = null,
        public ?int $epicId = null,
        public ?int $sprintId = null,
        public array $assigneeNames = [],
    ) {
    }
}
