<?php

declare(strict_types=1);

namespace App\Application\Issue\Command;

final class UpdateIssueCommand
{
    public int $id = 0;

    /** @param list<string> $assigneeNames */
    public function __construct(
        public string $title,
        public string $type,
        public string $priority,
        public ?string $description = null,
        public ?string $dueAt = null,
        public ?int $epicId = null,
        public ?int $sprintId = null,
        public ?int $storyPoints = null,
        public array $assigneeNames = [],
    ) {
    }
}
