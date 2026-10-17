<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

use App\Domain\Project\Entity\ProjectMember;

interface ProjectMemberRepositoryInterface
{
    /** @return ProjectMember[] */
    public function findForProject(int $projectId): array;

    public function findForProjectAndUser(int $projectId, string $userId): ?ProjectMember;

    /** @return int[] */
    public function findProjectIdsForUser(string $userId): array;

    public function countOwners(int $projectId): int;

    public function save(ProjectMember $member): void;

    public function remove(ProjectMember $member): void;
}
