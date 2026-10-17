<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class ConfirmTotpSetupCommand
{
    public function __construct(
        public string $userId,
        public string $code,
    ) {
    }
}
