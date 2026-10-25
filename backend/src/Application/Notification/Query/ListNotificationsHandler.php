<?php

declare(strict_types=1);

namespace App\Application\Notification\Query;

use App\Application\Notification\DTO\NotificationResponse;

final class ListNotificationsHandler
{
    public function __construct(private readonly NotificationReadModelInterface $notifications)
    {
    }

    /** @return NotificationResponse[] */
    public function handle(ListNotificationsQuery $query): array
    {
        return array_map(
            NotificationResponse::from(...),
            $this->notifications->findForUser($query->userId)
        );
    }
}
