<?php

declare(strict_types=1);

namespace App\Application\Notification\Command;

final readonly class DeleteNotificationCommand
{
    public function __construct(
        public string $id,
        public string $userId,
    ) {
    }
}
