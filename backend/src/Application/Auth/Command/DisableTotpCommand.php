<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class DisableTotpCommand
{
    public function __construct(
        public string $userId,
        public string $password,
    ) {
    }
}
