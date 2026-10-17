<?php

declare(strict_types=1);

namespace App\Domain\Customer\ValueObject;

use InvalidArgumentException;

/**
 * Immutable composite VO. Copied by value (never referenced) onto an Order as a
 * shipping-address snapshot at draft-start time, so later changes to a Customer's
 * address never rewrite historical orders.
 */
final readonly class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public string $postalCode,
        public string $country,
    ) {
        if ('' === trim($street) || '' === trim($city) || '' === trim($postalCode) || '' === trim($country)) {
            throw new InvalidArgumentException('Address fields cannot be blank');
        }
    }

    public function equals(self $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country;
    }
}
