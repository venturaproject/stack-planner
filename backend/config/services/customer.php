<?php

use App\Application\Customer\Query\CustomerReadModelInterface;
use App\Domain\Customer\Repository\CustomerRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\DoctrineCustomerRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(CustomerRepositoryInterface::class, DoctrineCustomerRepository::class);
    $services->alias(CustomerReadModelInterface::class, DoctrineCustomerRepository::class);
};
