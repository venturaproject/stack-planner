<?php

declare(strict_types=1);

namespace App\Application\Notification\Command;

final readonly class MarkAllReadCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
