<?php

declare(strict_types=1);

namespace App\Application\Project\Command;

final readonly class AddProjectMemberCommand
{
    public function __construct(
        public int $projectId,
        public string $userId,
        public string $role,
    ) {
    }
}
