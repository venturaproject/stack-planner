<?php

declare(strict_types=1);

namespace App\Domain\Project\Repository;

use App\Domain\Project\Entity\Project;

interface ProjectRepositoryInterface
{
    public function findById(int $projectId): ?Project;

    public function save(Project $project): void;
}
