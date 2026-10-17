<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Order\Command\AddOrderLineCommand;
use App\Application\Order\DTO\OrderResponse;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\ApiPlatform\Resource\OrderResource;

/** @implements ProcessorInterface<OrderResource, OrderResource> */
final class AddOrderLineProcessor implements ProcessorInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrderResource
    {
        /** @var OrderResource $data */
        /** @var OrderResponse $result */
        $result = $this->commandBus->dispatch(new AddOrderLineCommand(
            orderId: $data->orderId,
            customerId: $data->customerId,
            productId: (int) $data->productId,
            quantity: $data->quantity ?? 1,
        ));

        return OrderResource::fromResponse($result);
    }
}
