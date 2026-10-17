<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->import(
        resource: [
            'path'      => '../src/Infrastructure/Http/Controller/',
            'namespace' => 'App\Infrastructure\Http\Controller',
        ],
        type: 'attribute',
    );
};
