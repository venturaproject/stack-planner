<?php

declare(strict_types=1);

namespace App\Application\Issue\Query;

final readonly class ListIssuesQuery
{
    public function __construct(public ?int $projectId = null)
    {
    }
}
