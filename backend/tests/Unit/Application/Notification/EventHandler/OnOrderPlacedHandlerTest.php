<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Notification\EventHandler;

use App\Application\Notification\Command\CreateNotificationCommand;
use App\Application\Notification\EventHandler\OnOrderPlacedHandler;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Domain\Order\Event\OrderPlaced;
use App\Infrastructure\Outbox\AuditContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class OnOrderPlacedHandlerTest extends TestCase
{
    private function auditContext(?string $outboxEventId): AuditContext
    {
        $context = new AuditContext(
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(RequestStack::class),
        );
        $context->setOutboxEventId($outboxEventId);

        return $context;
    }

    public function testDispatchesNotificationWithFormattedAmount(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus->expects(self::once())->method('dispatch')
            ->with(self::callback(static function (CreateNotificationCommand $cmd) {
                self::assertStringContainsString('#42', $cmd->message);
                self::assertStringContainsString('19.99', $cmd->message);
                self::assertStringContainsString('USD', $cmd->message);

                return true;
            }));

        $handler = new OnOrderPlacedHandler($commandBus, $this->auditContext(null));

        $handler->onOrderPlaced(new OrderPlaced(42, 1, 1999, 'USD'));
    }

    public function testPassesTheCurrentOutboxEventIdThroughForDedup(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus->expects(self::once())->method('dispatch')
            ->with(self::callback(static function (CreateNotificationCommand $cmd) {
                self::assertSame('outbox-event-1', $cmd->outboxEventId);

                return true;
            }));

        $handler = new OnOrderPlacedHandler($commandBus, $this->auditContext('outbox-event-1'));

        $handler->onOrderPlaced(new OrderPlaced(42, 1, 1999, 'USD'));
    }
}
