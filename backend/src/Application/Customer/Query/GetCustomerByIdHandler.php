<?php

declare(strict_types=1);

namespace App\Application\Customer\Query;

use App\Application\Customer\DTO\CustomerResponse;
use App\Domain\Customer\Exception\CustomerNotFoundException;

final class GetCustomerByIdHandler
{
    public function __construct(private readonly CustomerReadModelInterface $customers)
    {
    }

    public function handle(GetCustomerByIdQuery $query): CustomerResponse
    {
        $customer = $this->customers->findById($query->id);
        if (null === $customer) {
            throw new CustomerNotFoundException();
        }

        return CustomerResponse::from($customer);
    }
}
