<?php

declare(strict_types=1);

namespace App\Application\Product\Query;

use DateTimeImmutable;

final readonly class ListProductsQuery
{
    public function __construct(
        public int $page,
        public int $size,
        public ?string $search,
        public ?string $stockLevel = null,
        public ?DateTimeImmutable $createdFrom = null,
        public ?DateTimeImmutable $createdTo = null,
        public ?string $stockStatus = null,
        public ?int $priceMin = null,
        public ?int $priceMax = null,
    ) {
    }
}
