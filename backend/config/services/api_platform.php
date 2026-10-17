<?php

use App\Infrastructure\ApiPlatform\OpenApi\ApiClientSecurityDecorator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ApiClientSecurityDecorator::class)
        ->decorate('api_platform.openapi.factory')
        ->autowire();
};
