<?php

use App\Application\AuditLog\Query\AuditLogReadModelInterface;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Infrastructure\AuditLog\AuditLogRecorder;
use App\Infrastructure\AuditLog\AuditLogSubscriber;
use App\Infrastructure\Persistence\Doctrine\DoctrineAuditLogRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(AuditLogRepositoryInterface::class, DoctrineAuditLogRepository::class);
    $services->alias(AuditLogReadModelInterface::class, DoctrineAuditLogRepository::class);

    $services->set(AuditLogRecorder::class)->autowire();

    $services->set(AuditLogSubscriber::class)->autowire()->autoconfigure();
};
