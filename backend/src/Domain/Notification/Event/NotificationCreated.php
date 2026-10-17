<?php

declare(strict_types=1);

namespace App\Domain\Notification\Event;

use App\Domain\Shared\DomainEvent;

/**
 * Raised on every notification creation but currently has no subscriber —
 * intentional, not an oversight: a notification is already itself the side
 * effect of some other event (OnOrderPlacedHandler, OnStockAdjustedHandler,
 * OnUserCreatedHandler), so reacting to its creation would mean chaining a
 * side effect off a side effect. Still goes through the outbox like any other
 * domain event, so it's here and ready the moment something legitimately
 * needs to react to it (e.g. a push notification dispatch).
 */
final class NotificationCreated extends DomainEvent
{
    public function __construct(
        public readonly ?string $notificationId,
        public readonly string $title,
        public readonly ?string $userId,
    ) {
        parent::__construct();
    }
}
