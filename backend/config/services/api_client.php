<?php

use App\Domain\ApiClient\Repository\ApiClientRepositoryInterface;
use App\Domain\ApiClient\Repository\ApiClientTokenRepositoryInterface;
use App\Application\ApiClient\Query\ApiClientReadModelInterface;
use App\Infrastructure\Persistence\Doctrine\DoctrineApiClientRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineApiClientTokenRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(ApiClientRepositoryInterface::class, DoctrineApiClientRepository::class);
    $services->alias(ApiClientReadModelInterface::class, DoctrineApiClientRepository::class);
    $services->alias(ApiClientTokenRepositoryInterface::class, DoctrineApiClientTokenRepository::class);
};
