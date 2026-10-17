<?php

use App\Domain\Notification\Repository\NotificationRepositoryInterface;
use App\Application\Notification\Query\NotificationReadModelInterface;
use App\Infrastructure\Persistence\Doctrine\DoctrineNotificationRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(NotificationRepositoryInterface::class, DoctrineNotificationRepository::class);
    $services->alias(NotificationReadModelInterface::class, DoctrineNotificationRepository::class);
};
