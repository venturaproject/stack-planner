<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Notification\Command;

use App\Application\Notification\Command\MarkAllReadCommand;
use App\Application\Notification\Command\MarkAllReadHandler;
use App\Domain\Notification\Repository\NotificationRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class MarkAllReadHandlerTest extends TestCase
{
    public function testDelegatesToRepositoryForUser(): void
    {
        $notifications = $this->createMock(NotificationRepositoryInterface::class);
        $notifications->expects(self::once())->method('markAllReadForUser')->with('user-1');

        $handler = new MarkAllReadHandler($notifications);

        $handler->handle(new MarkAllReadCommand('user-1'));
    }
}
