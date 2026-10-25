<?php

use App\Application\Product\Command\RemoveProductImageHandler;
use App\Application\Product\Command\UploadProductImageHandler;
use App\Application\Product\Query\ProductReadModelInterface;
use App\Application\Shared\Storage\FileStorageInterface;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Infrastructure\Http\Controller\Product\ProductController;
use App\Infrastructure\Persistence\Doctrine\DoctrineProductRepository;
use App\Infrastructure\Storage\LocalFileStorage;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->alias(ProductRepositoryInterface::class, DoctrineProductRepository::class);
    $services->alias(ProductReadModelInterface::class, DoctrineProductRepository::class);
    $services->alias(FileStorageInterface::class, LocalFileStorage::class);

    $services->set(UploadProductImageHandler::class)
        ->autowire()->autoconfigure()
        ->tag('messenger.message_handler', ['bus' => 'command.bus', 'method' => 'handle']);

    $services->set(RemoveProductImageHandler::class)
        ->autowire()->autoconfigure()
        ->tag('messenger.message_handler', ['bus' => 'command.bus', 'method' => 'handle']);

    $services->set(LocalFileStorage::class)
        ->autowire()
        ->arg('$mediaDir', '%env(MEDIA_DIR)%');

    $services->set(ProductController::class)
        ->autowire()->autoconfigure()
        ->arg('$mediaDir', '%env(MEDIA_DIR)%');
};
