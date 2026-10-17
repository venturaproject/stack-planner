<?php

use App\Application\Planning\Query\PlanningReadModelInterface;
use App\Infrastructure\Http\Controller\Planning\PlanningController;
use App\Infrastructure\Persistence\Doctrine\DoctrinePlanningReadModel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->alias(PlanningReadModelInterface::class, DoctrinePlanningReadModel::class);
    $services->set(PlanningController::class)->autowire()->autoconfigure();
};
