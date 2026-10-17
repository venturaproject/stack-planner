<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Product\Command\UpdateProductCommand;
use App\Application\Product\DTO\ProductResponse;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\ApiPlatform\Resource\ProductResource;
use App\Infrastructure\ApiPlatform\UriVariable;

/** @implements ProcessorInterface<ProductResource, ProductResource> */
final class UpdateProductProcessor implements ProcessorInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductResource
    {
        /** @var ProductResource $data */
        $cmd = new UpdateProductCommand(
            name: $data->name,
            description: $data->description,
            priceAmountMinorUnits: $data->priceAmountMinorUnits,
            priceCurrency: $data->priceCurrency,
        );
        $cmd->id = UriVariable::int($uriVariables, 'id');

        /** @var ProductResponse $result */
        $result = $this->commandBus->dispatch($cmd);

        return ProductResource::fromResponse($result);
    }
}
