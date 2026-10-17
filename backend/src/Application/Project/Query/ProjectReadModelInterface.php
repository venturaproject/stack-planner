<?php

declare(strict_types=1);

namespace App\Application\Project\Query;

use App\Domain\Project\Entity\Project;

interface ProjectReadModelInterface
{
    /** @return Project[] */
    public function findVisibleTo(?string $memberUserId): array;

    public function countIssues(int $projectId): int;

    public function countIssuesByStatus(int $projectId, string $status): int;

    public function activeSprintName(int $projectId): ?string;

    /** @return list<array{name: string, avatar: ?string, role: string}> */
    public function members(int $projectId): array;
}
