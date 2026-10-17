<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\AuthResponse;
use DateTimeImmutable;

final readonly class LoginResult
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public DateTimeImmutable $refreshExpires,
        public AuthResponse $auth,
    ) {
    }
}
