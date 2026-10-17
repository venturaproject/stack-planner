<?php

declare(strict_types=1);

namespace App\Application\Shared\DTO;

final readonly class TabularExport
{
    /** @param array<array<scalar>> $rows */
    public function __construct(
        public array $rows,
        public bool $limitExceeded,
    ) {
    }
}
