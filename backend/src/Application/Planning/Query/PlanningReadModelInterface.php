<?php

declare(strict_types=1);

namespace App\Application\Planning\Query;

use App\Domain\Planning\Entity\Epic;
use App\Domain\Planning\Entity\Sprint;

interface PlanningReadModelInterface
{
    /** @return Epic[] */
    public function findEpics(?int $projectId): array;

    /** @return Sprint[] */
    public function findSprints(?int $projectId): array;
}
