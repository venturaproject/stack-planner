<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Notification\Command;

use App\Application\Notification\Command\MarkReadCommand;
use App\Application\Notification\Command\MarkReadHandler;
use App\Domain\Notification\Entity\Notification;
use App\Domain\Notification\Exception\NotificationNotFoundException;
use App\Domain\Notification\Repository\NotificationRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class MarkReadHandlerTest extends TestCase
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

    public function testMarksOwnNotificationAsRead(): void
    {
        $n = $this->notification('user-1');
        $notifications = $this->createStub(NotificationRepositoryInterface::class);
        $notifications->method('findById')->willReturn($n);
        $notifications->method('save')->willReturnArgument(0);

        $handler = new MarkReadHandler($notifications);

        $response = $handler->handle(new MarkReadCommand('notif-1', 'user-1'));

        self::assertNotNull($response->readAt);
    }

    public function testRejectsMutatingBroadcastNotification(): void
    {
        $n = $this->notification(null);
        $notifications = $this->createStub(NotificationRepositoryInterface::class);
        $notifications->method('findById')->willReturn($n);
        $handler = new MarkReadHandler($notifications);

        $this->expectException(NotificationNotFoundException::class);

        $handler->handle(new MarkReadCommand('notif-1', 'any-user'));
    }

    public function testThrowsWhenNotificationBelongsToAnotherUser(): void
    {
        $n = $this->notification('user-1');
        $notifications = $this->createStub(NotificationRepositoryInterface::class);
        $notifications->method('findById')->willReturn($n);

        $handler = new MarkReadHandler($notifications);

        $this->expectException(NotificationNotFoundException::class);

        $handler->handle(new MarkReadCommand('notif-1', 'someone-else'));
    }

    public function testThrowsWhenNotificationNotFound(): void
    {
        $notifications = $this->createStub(NotificationRepositoryInterface::class);
        $notifications->method('findById')->willReturn(null);

        $handler = new MarkReadHandler($notifications);

        $this->expectException(NotificationNotFoundException::class);

        $handler->handle(new MarkReadCommand('missing', 'user-1'));
    }
}
