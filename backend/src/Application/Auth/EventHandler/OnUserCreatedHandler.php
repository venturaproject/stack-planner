<?php

declare(strict_types=1);

namespace App\Application\Auth\EventHandler;

use App\Application\Notification\Command\CreateNotificationCommand;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Outbox\AuditContextInterface;
use App\Domain\Auth\Event\UserCreated;

final class OnUserCreatedHandler
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly AuditContextInterface $auditContext,
    ) {
    }

    public function handle(UserCreated $event): void
    {
        $this->commandBus->dispatch(new CreateNotificationCommand(
            title: 'New user registered',
            message: "The user {$event->email} has been created.",
            type: 'success',
            userId: null,
            outboxEventId: $this->auditContext->outboxEventId(),
        ));
    }
}
