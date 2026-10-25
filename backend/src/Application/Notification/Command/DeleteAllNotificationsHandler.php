<?php

declare(strict_types=1);

namespace App\Application\Notification\Command;

use App\Domain\Notification\Repository\NotificationRepositoryInterface;

final class DeleteAllNotificationsHandler
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications)
    {
    }

    public function handle(DeleteAllNotificationsCommand $cmd): void
    {
        $this->notifications->deleteAllForUser($cmd->userId);
    }
}
