<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class ActivateUserCommand
{
    public function __construct(
        public string $userId,
        public int $activationVersion,
        public string $password,
    ) {
    }
}
