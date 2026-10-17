<?php

declare(strict_types=1);

namespace App\Application\Search\Query;

final readonly class SearchQuery
{
    public function __construct(public string $term)
    {
    }
}
