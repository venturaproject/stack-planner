<?php

use App\Application\Search\Query\SearchReadModelInterface;
use App\Infrastructure\Persistence\Doctrine\DoctrineSearchReadModel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->services()->alias(SearchReadModelInterface::class, DoctrineSearchReadModel::class);
};
