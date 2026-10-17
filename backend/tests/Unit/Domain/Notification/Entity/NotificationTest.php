<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Notification\Entity;

use App\Domain\Notification\Entity\Notification;
use App\Domain\Notification\Event\NotificationCreated;
use PHPUnit\Framework\TestCase;

final class NotificationTest extends TestCase
{
    public function testIsUnreadUntilMarkedAsRead(): void
    {
        $notification = new Notification();

        self::assertFalse($notification->isRead());

        $notification->markAsRead();

        self::assertTrue($notification->isRead());
        self::assertNotNull($notification->getReadAt());
    }

    public function testMarkAsReadIsIdempotent(): void
    {
        $notification = new Notification();
        $notification->markAsRead();
        $firstReadAt = $notification->getReadAt();

        $notification->markAsRead();

        self::assertSame($firstReadAt, $notification->getReadAt());
    }

    public function testRecordCreatedRaisesNotificationCreated(): void
    {
        $notification = new Notification();
        $notification->setTitle('New order');
        $notification->setUserId('user-1');

        $notification->recordCreated();

        $events = $notification->pullEvents();
        self::assertInstanceOf(NotificationCreated::class, $events[0]);
        self::assertSame('New order', $events[0]->title);
        self::assertSame('user-1', $events[0]->userId);
    }
}
