<?php

use App\Application\Notification\EventHandler\OnOrderPlacedHandler;
use App\Application\Notification\EventHandler\OnStockAdjustedHandler;
use App\Application\Shared\Outbox\AuditContextInterface;
use App\Application\Shared\Outbox\EmailIdempotencyGuardInterface;
use App\Domain\Order\Event\OrderPlaced;
use App\Domain\Product\Event\StockAdjusted;
use App\Infrastructure\Outbox\AuditContext;
use App\Infrastructure\Outbox\EmailIdempotencyGuard;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(AuditContextInterface::class, AuditContext::class);
    $services->alias(EmailIdempotencyGuardInterface::class, EmailIdempotencyGuard::class);

    // Application event handlers are plain classes — no Symfony
    // EventSubscriberInterface. Wiring them to the events dispatched by
    // OutboxConsumer lives here instead, the same target shape ARCHITECTURE.md
    // describes for CQRS handler registration.
    $services->set(OnOrderPlacedHandler::class)
        ->autowire()->autoconfigure()
        ->tag('kernel.event_listener', ['event' => OrderPlaced::class, 'method' => 'onOrderPlaced']);

    $services->set(OnStockAdjustedHandler::class)
        ->autowire()->autoconfigure()
        ->tag('kernel.event_listener', ['event' => StockAdjusted::class, 'method' => 'onStockAdjusted']);
};
