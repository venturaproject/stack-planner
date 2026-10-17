<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Notification\EventHandler;

use App\Application\Notification\Command\CreateNotificationCommand;
use App\Application\Notification\EventHandler\OnStockAdjustedHandler;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Domain\Product\Event\StockAdjusted;
use App\Infrastructure\Outbox\AuditContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class OnStockAdjustedHandlerTest extends TestCase
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

    public function testDispatchesWarningWhenStockIsLow(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus->expects(self::once())->method('dispatch')
            ->with(self::isInstanceOf(CreateNotificationCommand::class));

        $handler = new OnStockAdjustedHandler($commandBus, $this->auditContext(null));

        $handler->onStockAdjusted(new StockAdjusted(1, -8, 3, 'order_placed'));
    }

    public function testDoesNothingWhenStockIsAboveThreshold(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus->expects(self::never())->method('dispatch');

        $handler = new OnStockAdjustedHandler($commandBus, $this->auditContext(null));

        $handler->onStockAdjusted(new StockAdjusted(1, 10, 20, 'restock'));
    }

    public function testPassesTheCurrentOutboxEventIdThroughForDedup(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus->expects(self::once())->method('dispatch')
            ->with(self::callback(static function (CreateNotificationCommand $cmd) {
                self::assertSame('outbox-event-1', $cmd->outboxEventId);

                return true;
            }));

        $handler = new OnStockAdjustedHandler($commandBus, $this->auditContext('outbox-event-1'));

        $handler->onStockAdjusted(new StockAdjusted(1, -8, 3, 'order_placed'));
    }
}
