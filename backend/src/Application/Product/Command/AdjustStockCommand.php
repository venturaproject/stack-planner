<?php

declare(strict_types=1);

namespace App\Application\Product\Command;

final class AdjustStockCommand
{
    public int $id = 0;

    public function __construct(
        public int $delta = 0,
        public string $reason = 'manual_adjustment',
    ) {
    }
}
