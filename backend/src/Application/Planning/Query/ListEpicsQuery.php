<?php

declare(strict_types=1);

namespace App\Application\Planning\Query;

final readonly class ListEpicsQuery
{
    public function __construct(public ?int $projectId = null)
    {
    }
}
