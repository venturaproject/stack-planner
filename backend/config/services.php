<?php

use App\Application\Shared\Event\DomainEventPublisherInterface;
use App\Infrastructure\Outbox\OutboxPublisher;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->load('App\\', '../src/')
        ->exclude([
            '../src/Domain/',
            '../src/Kernel.php',
        ]);

    $services->alias(DomainEventPublisherInterface::class, OutboxPublisher::class);

    $container->import('services/*.php');
};
