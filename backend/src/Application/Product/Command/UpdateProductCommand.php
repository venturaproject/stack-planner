<?php

declare(strict_types=1);

namespace App\Application\Product\Command;

final class UpdateProductCommand
{
    public int $id = 0;

    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?int $priceAmountMinorUnits = null,
        public ?string $priceCurrency = null,
    ) {
    }
}
