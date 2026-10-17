<?php

declare(strict_types=1);

namespace App\Application\Auth\DTO;

final readonly class AuthResponse
{
    public function __construct(
        private string $accessToken,
        public UserResponse $user,
    ) {
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }
}
