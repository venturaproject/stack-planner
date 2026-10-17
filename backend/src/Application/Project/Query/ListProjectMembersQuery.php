<?php

declare(strict_types=1);

namespace App\Application\Project\Query;

final readonly class ListProjectMembersQuery
{
    public function __construct(public int $projectId)
    {
    }
}
