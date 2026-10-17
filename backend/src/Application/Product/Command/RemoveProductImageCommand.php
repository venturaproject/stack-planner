<?php

declare(strict_types=1);

namespace App\Application\Product\Command;

final readonly class RemoveProductImageCommand
{
    public function __construct(
        public int $id,
    ) {
    }
}
