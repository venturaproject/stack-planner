<?php

use App\Domain\Issue\Repository\IssueAttachmentRepositoryInterface;
use App\Infrastructure\Http\Controller\Issue\IssueImageUploadController;
use App\Infrastructure\Persistence\Doctrine\DoctrineIssueAttachmentRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->alias(IssueAttachmentRepositoryInterface::class, DoctrineIssueAttachmentRepository::class);
    $services->set(IssueImageUploadController::class)->autowire()->autoconfigure();
};
