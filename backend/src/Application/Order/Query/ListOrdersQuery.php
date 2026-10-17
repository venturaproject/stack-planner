<?php

declare(strict_types=1);

namespace App\Application\Order\Query;

use DateTimeImmutable;

final readonly class ListOrdersQuery
{
    public function __construct(
        public int $page,
        public int $size,
        public ?int $customerId = null,
        public ?string $status = null,
        public ?DateTimeImmutable $createdFrom = null,
        public ?DateTimeImmutable $createdTo = null,
    ) {
    }
}
