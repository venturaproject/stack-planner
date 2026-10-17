<?php

declare(strict_types=1);

namespace App\Application\Customer\Query;

use DateTimeImmutable;

final readonly class ExportCustomersQuery
{
    /** @param int[] $ids */
    public function __construct(
        public array $ids,
        public ?string $search,
        public ?bool $hasAddress,
        public bool $all,
        public ?DateTimeImmutable $createdFrom = null,
        public ?DateTimeImmutable $createdTo = null,
    ) {
    }
}
