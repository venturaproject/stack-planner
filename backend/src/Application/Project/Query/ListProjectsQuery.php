<?php

declare(strict_types=1);

namespace App\Application\Project\Query;

final readonly class ListProjectsQuery
{
    public function __construct(public ?string $memberUserId = null)
    {
    }
}
