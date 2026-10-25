<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $applicationPath = dirname(__DIR__, 2).'/src/Application';

    foreach (glob($applicationPath.'/*/{Command,Query}/*Handler.php', GLOB_BRACE) ?: [] as $handlerPath) {
        $relativePath = substr($handlerPath, strlen($applicationPath) + 1, -4);
        $class = 'App\\Application\\'.str_replace('/', '\\', $relativePath);
        $bus = str_contains($relativePath, '/Command/') ? 'command.bus' : 'query.bus';

        $services->set($class)->autowire()->tag('messenger.message_handler', [
            'bus' => $bus,
            'method' => 'handle',
        ]);
    }
};
