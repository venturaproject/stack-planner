<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Product\Command\CreateProductCommand;
use App\Application\Product\DTO\ProductResponse;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\ApiPlatform\Resource\ProductResource;

/** @implements ProcessorInterface<ProductResource, ProductResource> */
final class CreateProductProcessor implements ProcessorInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductResource
    {
        /** @var ProductResource $data */
        /** @var ProductResponse $result */
        $result = $this->commandBus->dispatch(new CreateProductCommand(
            sku: (string) $data->sku,
            name: (string) $data->name,
            description: $data->description,
            priceAmountMinorUnits: $data->priceAmountMinorUnits ?? 0,
            priceCurrency: $data->priceCurrency ?? 'USD',
            initialStock: $data->initialStock ?? 0,
        ));

        return ProductResource::fromResponse($result);
    }
}
