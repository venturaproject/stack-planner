<?php

declare(strict_types=1);

namespace App\Application\Notification\Command;

use App\Application\Notification\DTO\NotificationResponse;
use App\Domain\Notification\Entity\Notification;
use App\Domain\Notification\Repository\NotificationRepositoryInterface;

final class CreateNotificationHandler
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications)
    {
    }

    public function handle(CreateNotificationCommand $cmd): NotificationResponse
    {
        // The outbox only guarantees at-least-once delivery: a crash between the
        // triggering event's dispatch and its outbox row being marked processed
        // replays it, which would otherwise create a second identical notification.
        if (null !== $cmd->outboxEventId) {
            $existing = $this->notifications->findByOutboxEventId($cmd->outboxEventId);
            if (null !== $existing) {
                return NotificationResponse::from($existing);
            }
        }

        $n = new Notification();
        $n->setTitle($cmd->title);
        $n->setMessage($cmd->message);
        $n->setType($cmd->type);
        $n->setUserId($cmd->userId);
        $n->setOutboxEventId($cmd->outboxEventId);
        $n->recordCreated();

        return NotificationResponse::from($this->notifications->save($n));
    }
}
