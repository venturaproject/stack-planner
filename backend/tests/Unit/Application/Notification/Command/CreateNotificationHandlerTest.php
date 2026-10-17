<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Notification\Command;

use App\Application\Notification\Command\CreateNotificationCommand;
use App\Application\Notification\Command\CreateNotificationHandler;
use App\Domain\Notification\Entity\Notification;
use App\Domain\Notification\Repository\NotificationRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class CreateNotificationHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testCreatesNotification(): void
    {
        $notifications = $this->createStub(NotificationRepositoryInterface::class);
        $notifications->method('save')->willReturnCallback(static function (Notification $n) {
            self::setId($n, 'notif-1');

            return $n;
        });

        $handler = new CreateNotificationHandler($notifications);

        $response = $handler->handle(new CreateNotificationCommand(
            title: 'New order',
            message: 'You have a new order',
            type: 'success',
            userId: 'user-1',
        ));

        self::assertSame('notif-1', $response->id);
        self::assertSame('New order', $response->title);
        self::assertNull($response->readAt);
    }

    public function testRedeliveredOutboxEventDoesNotCreateADuplicate(): void
    {
        $existing = new Notification();
        self::setId($existing, 'notif-1');
        $existing->setTitle('New order');
        $existing->setMessage('You have a new order');
        $existing->setType('success');
        $existing->setUserId('user-1');
        $existing->setOutboxEventId('outbox-event-1');

        $notifications = $this->createMock(NotificationRepositoryInterface::class);
        $notifications->expects(self::once())->method('findByOutboxEventId')->with('outbox-event-1')->willReturn($existing);
        $notifications->expects(self::never())->method('save');

        $handler = new CreateNotificationHandler($notifications);

        $response = $handler->handle(new CreateNotificationCommand(
            title: 'New order',
            message: 'You have a new order',
            type: 'success',
            userId: 'user-1',
            outboxEventId: 'outbox-event-1',
        ));

        self::assertSame('notif-1', $response->id);
    }

    public function testFirstDeliveryOfAnOutboxEventIsRecordedWithItsId(): void
    {
        $notifications = $this->createMock(NotificationRepositoryInterface::class);
        $notifications->expects(self::once())->method('findByOutboxEventId')->with('outbox-event-1')->willReturn(null);
        $notifications->expects(self::once())->method('save')->with(self::callback(static function (Notification $n) {
            self::setId($n, 'notif-1');
            self::assertSame('outbox-event-1', $n->getOutboxEventId());

            return true;
        }))->willReturnArgument(0);

        $handler = new CreateNotificationHandler($notifications);

        $handler->handle(new CreateNotificationCommand(
            title: 'New order',
            message: 'You have a new order',
            type: 'success',
            userId: 'user-1',
            outboxEventId: 'outbox-event-1',
        ));
    }
}
