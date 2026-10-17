<?php

use App\Application\Project\Query\ProjectReadModelInterface;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use App\Infrastructure\Http\Controller\Project\ProjectController;
use App\Infrastructure\Persistence\Doctrine\DoctrineProjectRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->alias(ProjectRepositoryInterface::class, DoctrineProjectRepository::class);
    $services->alias(ProjectReadModelInterface::class, DoctrineProjectRepository::class);
    $services->set(ProjectController::class)->autowire()->autoconfigure();
};
