<?php

declare(strict_types=1);

namespace App\Application\Notification\Query;

final readonly class ListNotificationsQuery
{
    public function __construct(
        public string $userId,
    ) {
    }
}
