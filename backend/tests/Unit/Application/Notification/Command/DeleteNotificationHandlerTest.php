<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Notification\Command;

use App\Application\Notification\Command\DeleteNotificationCommand;
use App\Application\Notification\Command\DeleteNotificationHandler;
use App\Domain\Notification\Entity\Notification;
use App\Domain\Notification\Exception\NotificationNotFoundException;
use App\Domain\Notification\Repository\NotificationRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class DeleteNotificationHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function notification(?string $userId): Notification
    {
        $n = new Notification();
        $n->setTitle('Title');
        $n->setMessage('Message');
        $n->setUserId($userId);
        self::setId($n, 'notif-1');

        return $n;
    }

    public function testDeletesOwnNotification(): void
    {
        $n = $this->notification('user-1');
        $notifications = $this->createMock(NotificationRepositoryInterface::class);
        $notifications->method('findById')->willReturn($n);
        $notifications->expects(self::once())->method('delete')->with($n);

        $handler = new DeleteNotificationHandler($notifications);

        $handler->handle(new DeleteNotificationCommand('notif-1', 'user-1'));
    }

    public function testThrowsWhenNotificationBelongsToAnotherUser(): void
    {
        $n = $this->notification('user-1');
        $notifications = $this->createMock(NotificationRepositoryInterface::class);
        $notifications->method('findById')->willReturn($n);
        $notifications->expects(self::never())->method('delete');

        $handler = new DeleteNotificationHandler($notifications);

        $this->expectException(NotificationNotFoundException::class);

        $handler->handle(new DeleteNotificationCommand('notif-1', 'someone-else'));
    }

    public function testThrowsWhenNotificationNotFound(): void
    {
        $notifications = $this->createStub(NotificationRepositoryInterface::class);
        $notifications->method('findById')->willReturn(null);

        $handler = new DeleteNotificationHandler($notifications);

        $this->expectException(NotificationNotFoundException::class);

        $handler->handle(new DeleteNotificationCommand('missing', 'user-1'));
    }
}
