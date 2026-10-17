<?php

declare(strict_types=1);

namespace App\Domain\Issue\Repository;

use App\Domain\Issue\Entity\Issue;

interface IssueRepositoryInterface
{
    public function findById(int $id): ?Issue;

    public function nextNumberForProject(int $projectId): int;

    public function save(Issue $issue): void;
}
