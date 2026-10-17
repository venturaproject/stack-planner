<?php

declare(strict_types=1);

namespace App\Application\Notification\Query;

use App\Domain\Notification\Entity\Notification;

interface NotificationReadModelInterface
{
    /** @return list<Notification> */
    public function findForUser(string $userId): array;
}
