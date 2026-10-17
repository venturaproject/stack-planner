<?php

declare(strict_types=1);

namespace App\Application\Customer\Command;

final class RegisterCustomerCommand
{
    public function __construct(
        public string $name = '',
        public string $email = '',
        public ?string $addressStreet = null,
        public ?string $addressCity = null,
        public ?string $addressPostalCode = null,
        public ?string $addressCountry = null,
    ) {
    }
}
