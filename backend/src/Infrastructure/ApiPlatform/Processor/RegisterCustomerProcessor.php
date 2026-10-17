<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Customer\Command\RegisterCustomerCommand;
use App\Application\Customer\DTO\CustomerResponse;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\ApiPlatform\Resource\CustomerResource;

/** @implements ProcessorInterface<CustomerResource, CustomerResource> */
final class RegisterCustomerProcessor implements ProcessorInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CustomerResource
    {
        /** @var CustomerResource $data */
        /** @var CustomerResponse $result */
        $result = $this->commandBus->dispatch(new RegisterCustomerCommand(
            name: (string) $data->name,
            email: (string) $data->email,
            addressStreet: $data->addressStreet,
            addressCity: $data->addressCity,
            addressPostalCode: $data->addressPostalCode,
            addressCountry: $data->addressCountry,
        ));

        return CustomerResource::fromResponse($result);
    }
}
