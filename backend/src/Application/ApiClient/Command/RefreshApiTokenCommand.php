<?php

declare(strict_types=1);

namespace App\Application\ApiClient\Command;

final readonly class RefreshApiTokenCommand
{
    public function __construct(
        public string $refreshToken,
    ) {
    }
}
