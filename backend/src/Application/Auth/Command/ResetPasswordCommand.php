<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class ResetPasswordCommand
{
    public function __construct(
        public string $userId,
        public int $version,
        public string $password,
    ) {
    }
}
