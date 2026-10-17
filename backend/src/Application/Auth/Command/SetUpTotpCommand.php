<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class SetUpTotpCommand
{
    public function __construct(public string $userId)
    {
    }
}
