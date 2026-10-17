<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Notification\Command;

use App\Application\Notification\Command\DeleteAllNotificationsCommand;
use App\Application\Notification\Command\DeleteAllNotificationsHandler;
use App\Domain\Notification\Repository\NotificationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class DeleteAllNotificationsHandlerTest extends TestCase
{
    public function testDelegatesToRepositoryForUser(): void
    {
        $notifications = $this->createMock(NotificationRepositoryInterface::class);
        $notifications->expects(self::once())->method('deleteAllForUser')->with('user-1');

        $handler = new DeleteAllNotificationsHandler($notifications);

        $handler->handle(new DeleteAllNotificationsCommand('user-1'));
    }
}
