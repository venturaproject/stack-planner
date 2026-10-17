<?php

use App\Application\Issue\Query\IssueReadModelInterface;
use App\Domain\Issue\Repository\IssueRepositoryInterface;
use App\Infrastructure\Http\Controller\Issue\IssueController;
use App\Infrastructure\Persistence\Doctrine\DoctrineIssueReadModel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->alias(IssueReadModelInterface::class, DoctrineIssueReadModel::class);
    $services->alias(IssueRepositoryInterface::class, DoctrineIssueReadModel::class);
    $services->set(IssueController::class)->autowire()->autoconfigure();
};
