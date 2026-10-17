<?php

declare(strict_types=1);

namespace App\Application\Order\Query;

use DateTimeImmutable;

final readonly class ExportOrdersQuery
{
    /** @param int[] $ids */
    public function __construct(
        public array $ids,
        public ?int $customerId,
        public ?string $status,
        public bool $all,
        public ?DateTimeImmutable $createdFrom = null,
        public ?DateTimeImmutable $createdTo = null,
    ) {
    }
}
