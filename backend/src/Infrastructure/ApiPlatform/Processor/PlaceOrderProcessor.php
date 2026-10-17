<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Order\Command\PlaceOrderCommand;
use App\Application\Order\DTO\OrderResponse;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\ApiPlatform\Resource\OrderResource;
use App\Infrastructure\ApiPlatform\UriVariable;

/** @implements ProcessorInterface<OrderResource, OrderResource> */
final class PlaceOrderProcessor implements ProcessorInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrderResource
    {
        /** @var OrderResponse $result */
        $result = $this->commandBus->dispatch(new PlaceOrderCommand(UriVariable::int($uriVariables, 'id')));

        return OrderResource::fromResponse($result);
    }
}
