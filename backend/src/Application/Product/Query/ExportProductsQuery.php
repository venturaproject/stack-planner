<?php

declare(strict_types=1);

namespace App\Application\Product\Query;

use DateTimeImmutable;

final readonly class ExportProductsQuery
{
    /** @param int[] $ids */
    public function __construct(
        public array $ids,
        public ?string $search,
        public ?string $stockLevel,
        public bool $all,
        public ?DateTimeImmutable $createdFrom = null,
        public ?DateTimeImmutable $createdTo = null,
        public ?string $stockStatus = null,
        public ?int $priceMin = null,
        public ?int $priceMax = null,
    ) {
    }
}
