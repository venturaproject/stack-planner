<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class RefreshTokenCommand
{
    public function __construct(
        public string $tokenValue,
    ) {
    }
}
