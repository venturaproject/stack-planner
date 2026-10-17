<?php

use App\Application\Project\Query\ProjectReadModelInterface;
use App\Application\Shared\Security\CurrentUserIdProviderInterface;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use App\Infrastructure\Http\Controller\Project\ProjectController;
use App\Infrastructure\Persistence\Doctrine\DoctrineProjectRepository;
use App\Domain\Project\Repository\ProjectMemberRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\DoctrineProjectMemberRepository;
use App\Infrastructure\Security\Symfony\SymfonyCurrentUserIdProvider;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->alias(ProjectRepositoryInterface::class, DoctrineProjectRepository::class);
    $services->alias(ProjectMemberRepositoryInterface::class, DoctrineProjectMemberRepository::class);
    $services->alias(ProjectReadModelInterface::class, DoctrineProjectRepository::class);
    $services->alias(CurrentUserIdProviderInterface::class, SymfonyCurrentUserIdProvider::class);
    $services->set(ProjectController::class)->autowire()->autoconfigure();
};
