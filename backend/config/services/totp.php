<?php

use App\Application\Shared\Totp\TotpServiceInterface;
use App\Infrastructure\Security\Totp\OtpHpTotpService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(TotpServiceInterface::class, OtpHpTotpService::class);
};
