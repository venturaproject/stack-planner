<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Notification\Query;

use App\Application\Notification\Query\ListNotificationsHandler;
use App\Application\Notification\Query\ListNotificationsQuery;
use App\Application\Notification\Query\NotificationReadModelInterface;
use App\Domain\Notification\Entity\Notification;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class ListNotificationsHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsNotificationsForUser(): void
    {
        $n = new Notification();
        $n->setTitle('Title');
        $n->setMessage('Message');
        $n->setUserId('user-1');
        self::setId($n, 'notif-1');

        $notifications = $this->createStub(NotificationReadModelInterface::class);
        $notifications->method('findForUser')->willReturn([$n]);

        $handler = new ListNotificationsHandler($notifications);

        $response = $handler->handle(new ListNotificationsQuery('user-1'));

        self::assertCount(1, $response);
        self::assertSame('Title', $response[0]->title);
    }
}
