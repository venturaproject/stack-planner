<?php

declare(strict_types=1);

namespace App\Application\Notification\EventHandler;

use App\Application\Notification\Command\CreateNotificationCommand;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Outbox\AuditContextInterface;
use App\Domain\Order\Event\OrderPlaced;

final class OnOrderPlacedHandler
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly AuditContextInterface $auditContext,
    ) {
    }

    public function onOrderPlaced(OrderPlaced $event): void
    {
        $amount = number_format($event->totalAmountMinorUnits / 100, 2);

        $this->commandBus->dispatch(new CreateNotificationCommand(
            title: 'New order placed',
            message: \sprintf('Order #%d placed for %s %s.', $event->orderId, $amount, $event->currency),
            type: 'info',
            userId: null,
            outboxEventId: $this->auditContext->outboxEventId(),
        ));
    }
}
