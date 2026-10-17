<?php

declare(strict_types=1);

namespace App\Application\Notification\Command;

use App\Domain\Notification\Repository\NotificationRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class MarkAllReadHandler
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications)
    {
    }

    public function handle(MarkAllReadCommand $cmd): void
    {
        $this->notifications->markAllReadForUser($cmd->userId);
    }
}
