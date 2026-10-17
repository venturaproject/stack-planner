<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Customer\Command\UpdateCustomerAddressCommand;
use App\Application\Customer\DTO\CustomerResponse;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\ApiPlatform\Resource\CustomerResource;
use App\Infrastructure\ApiPlatform\UriVariable;

/** @implements ProcessorInterface<CustomerResource, CustomerResource> */
final class UpdateCustomerAddressProcessor implements ProcessorInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CustomerResource
    {
        /** @var CustomerResource $data */
        $cmd = new UpdateCustomerAddressCommand(
            street: (string) $data->addressStreet,
            city: (string) $data->addressCity,
            postalCode: (string) $data->addressPostalCode,
            country: (string) $data->addressCountry,
        );
        $cmd->id = UriVariable::int($uriVariables, 'id');

        /** @var CustomerResponse $result */
        $result = $this->commandBus->dispatch($cmd);

        return CustomerResource::fromResponse($result);
    }
}
