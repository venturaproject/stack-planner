<?php

declare(strict_types=1);

namespace App\Application\ApiClient\DTO;

final readonly class ApiTokenResponse
{
    /** @param string[] $scopes */
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public string $tokenType,
        public int $expiresIn,
        public array $scopes,
    ) {
    }
}
