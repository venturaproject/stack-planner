<?php

declare(strict_types=1);

namespace App\Application\Notification\EventHandler;

use App\Application\Notification\Command\CreateNotificationCommand;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Outbox\AuditContextInterface;
use App\Domain\Product\Event\StockAdjusted;

/**
 * A second, independent subscriber reacting to the same StockAdjusted event that
 * AuditLogSubscriber also listens to — each concern (audit trail vs. low-stock
 * alerting) stays fully decoupled from the Product aggregate and from each other.
 */
final class OnStockAdjustedHandler
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly AuditContextInterface $auditContext,
    ) {
    }

    public function onStockAdjusted(StockAdjusted $event): void
    {
        if ($event->newQuantity > self::LOW_STOCK_THRESHOLD) {
            return;
        }

        $this->commandBus->dispatch(new CreateNotificationCommand(
            title: 'Low stock warning',
            message: \sprintf('Product #%d has only %d unit(s) left in stock.', $event->productId, $event->newQuantity),
            type: 'warning',
            userId: null,
            outboxEventId: $this->auditContext->outboxEventId(),
        ));
    }
}
