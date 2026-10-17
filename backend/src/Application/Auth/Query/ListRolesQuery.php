<?php

declare(strict_types=1);

namespace App\Application\Auth\Query;

final readonly class ListRolesQuery
{
    public function __construct(
        public int $page,
        public int $size,
    ) {
    }
}
