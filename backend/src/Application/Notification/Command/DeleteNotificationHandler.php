<?php

declare(strict_types=1);

namespace App\Application\Notification\Command;

use App\Domain\Notification\Exception\NotificationNotFoundException;
use App\Domain\Notification\Repository\NotificationRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class DeleteNotificationHandler
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications)
    {
    }

    public function handle(DeleteNotificationCommand $cmd): void
    {
        $n = $this->notifications->findById($cmd->id)
            ?? throw new NotificationNotFoundException();

        if (null === $n->getUserId() || $n->getUserId() !== $cmd->userId) {
            throw new NotificationNotFoundException();
        }

        $this->notifications->delete($n);
    }
}
