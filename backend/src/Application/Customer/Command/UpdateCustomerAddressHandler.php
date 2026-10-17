<?php

declare(strict_types=1);

namespace App\Application\Customer\Command;

use App\Application\Customer\DTO\CustomerResponse;
use App\Domain\Customer\Exception\CustomerNotFoundException;
use App\Domain\Customer\Repository\CustomerRepositoryInterface;
use App\Domain\Customer\ValueObject\Address;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class UpdateCustomerAddressHandler
{
    public function __construct(private readonly CustomerRepositoryInterface $customers)
    {
    }

    public function handle(UpdateCustomerAddressCommand $cmd): CustomerResponse
    {
        $customer = $this->customers->findById($cmd->id);
        if (null === $customer) {
            throw new CustomerNotFoundException();
        }

        $customer->changeAddress(new Address($cmd->street, $cmd->city, $cmd->postalCode, $cmd->country));

        $this->customers->save($customer);

        return CustomerResponse::from($customer);
    }
}
