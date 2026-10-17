<?php

declare(strict_types=1);

namespace App\Application\Auth\Query;

final readonly class GetUserQuery
{
    public function __construct(
        public string $id,
    ) {
    }
}
