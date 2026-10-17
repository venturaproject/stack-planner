<?php

declare(strict_types=1);

namespace App\Application\Customer\DTO;

use App\Domain\Customer\Entity\Customer;
use DateTimeInterface;

final readonly class CustomerResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $addressStreet,
        public ?string $addressCity,
        public ?string $addressPostalCode,
        public ?string $addressCountry,
        public string $createdAt,
    ) {
    }

    public static function from(Customer $customer): self
    {
        $address = $customer->getAddress();

        return new self(
            id: $customer->requireId(),
            name: $customer->getName(),
            email: $customer->getEmail()->value(),
            addressStreet: $address?->street,
            addressCity: $address?->city,
            addressPostalCode: $address?->postalCode,
            addressCountry: $address?->country,
            createdAt: $customer->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
