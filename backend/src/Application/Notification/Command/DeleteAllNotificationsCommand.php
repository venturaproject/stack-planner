<?php

declare(strict_types=1);

namespace App\Application\Notification\Command;

final readonly class DeleteAllNotificationsCommand
{
    public function __construct(
        public string $userId,
    ) {
    }
}
