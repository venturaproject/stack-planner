<?php

declare(strict_types=1);

namespace App\Application\Customer\Command;

final class UpdateCustomerAddressCommand
{
    public int $id = 0;

    public function __construct(
        public string $street = '',
        public string $city = '',
        public string $postalCode = '',
        public string $country = '',
    ) {
    }
}
