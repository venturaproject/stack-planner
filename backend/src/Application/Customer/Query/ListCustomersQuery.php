<?php

declare(strict_types=1);

namespace App\Application\Customer\Query;

use DateTimeImmutable;

final readonly class ListCustomersQuery
{
    public function __construct(
        public int $page,
        public int $size,
        public ?string $search,
        public ?bool $hasAddress = null,
        public ?DateTimeImmutable $createdFrom = null,
        public ?DateTimeImmutable $createdTo = null,
    ) {
    }
}
