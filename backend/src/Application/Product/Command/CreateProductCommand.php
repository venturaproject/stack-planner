<?php

declare(strict_types=1);

namespace App\Application\Product\Command;

final class CreateProductCommand
{
    public function __construct(
        public string $sku,
        public string $name,
        public ?string $description = null,
        public int $priceAmountMinorUnits = 0,
        public string $priceCurrency = 'USD',
        public int $initialStock = 0,
    ) {
    }
}
