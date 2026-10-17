<?php

declare(strict_types=1);

namespace App\Application\Customer\Command;

use App\Application\Customer\DTO\CustomerResponse;
use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\Exception\EmailAlreadyRegisteredException;
use App\Domain\Customer\Repository\CustomerRepositoryInterface;
use App\Domain\Customer\ValueObject\Address;
use App\Domain\Customer\ValueObject\Email;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class RegisterCustomerHandler
{
    public function __construct(private readonly CustomerRepositoryInterface $customers)
    {
    }

    public function handle(RegisterCustomerCommand $cmd): CustomerResponse
    {
        $email = new Email($cmd->email);

        if (null !== $this->customers->findByEmail($email)) {
            throw new EmailAlreadyRegisteredException($email);
        }

        $address = null !== $cmd->addressStreet
            ? new Address($cmd->addressStreet, (string) $cmd->addressCity, (string) $cmd->addressPostalCode, (string) $cmd->addressCountry)
            : null;

        $customer = Customer::register($cmd->name, $email, $address);

        $this->customers->save($customer);

        return CustomerResponse::from($customer);
    }
}
