<?php

use App\Application\Shared\Mail\MailerInterface;
use App\Infrastructure\Mail\SymfonyMailer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(MailerInterface::class, SymfonyMailer::class);
};
