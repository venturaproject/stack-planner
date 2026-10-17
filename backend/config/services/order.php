<?php

use App\Application\Shared\Transaction\TransactionManagerInterface;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\DoctrineOrderRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineTransactionManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(OrderRepositoryInterface::class, DoctrineOrderRepository::class);
    $services->alias(OrderReadModelInterface::class, DoctrineOrderRepository::class);
    $services->alias(TransactionManagerInterface::class, DoctrineTransactionManager::class);
};
use App\Application\Order\Query\OrderReadModelInterface;
