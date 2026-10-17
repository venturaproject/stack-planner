<?php

declare(strict_types=1);

namespace App\Application\ApiClient\Command;

use App\Application\ApiClient\DTO\ApiClientResponse;

final readonly class CreateApiClientResult
{
    public function __construct(
        public ApiClientResponse $client,
        public string $secret,
    ) {
    }
}
