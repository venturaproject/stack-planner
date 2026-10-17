<?php

declare(strict_types=1);

namespace App\Application\Product\Command;

final readonly class UploadProductImageCommand
{
    public function __construct(
        public int $id,
        public string $tmpPath,
        public string $extension,
    ) {
    }
}
