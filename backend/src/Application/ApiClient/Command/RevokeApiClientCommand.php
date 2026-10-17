<?php

declare(strict_types=1);

namespace App\Application\ApiClient\Command;

final readonly class RevokeApiClientCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
