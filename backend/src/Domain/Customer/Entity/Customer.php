<?php

declare(strict_types=1);

namespace App\Domain\Customer\Entity;

use App\Domain\Customer\Event\CustomerAddressChanged;
use App\Domain\Customer\Event\CustomerRegistered;
use App\Domain\Customer\ValueObject\Address;
use App\Domain\Customer\ValueObject\Email;
use App\Domain\Shared\HasDomainEvents;
use App\Domain\Shared\RecordsDomainEvents;
use DateTimeImmutable;
use LogicException;

class Customer implements HasDomainEvents
{
    use RecordsDomainEvents;

    private ?int $id = null;
    private string $name;

    // Stored as a plain scalar column; the VO is reconstructed on read via getEmail().
    private string $email;

    // Address is optional and stored as four nullable scalar columns (see the Doctrine
    // mapping file) rather than a Doctrine embeddable.
    private ?string $addressStreet = null;
    private ?string $addressCity = null;
    private ?string $addressPostalCode = null;
    private ?string $addressCountry = null;

    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public static function register(string $name, Email $email, ?Address $address = null): self
    {
        $customer = new self();
        $customer->name = $name;
        $customer->email = $email->value();
        if (null !== $address) {
            $customer->setAddress($address);
        }
        $customer->raise(new CustomerRegistered($customer->id, $email->value(), $name));

        return $customer;
    }

    public function changeAddress(Address $newAddress): void
    {
        $this->setAddress($newAddress);
        $this->raise(new CustomerAddressChanged($this->id));
    }

    private function setAddress(Address $address): void
    {
        $this->addressStreet = $address->street;
        $this->addressCity = $address->city;
        $this->addressPostalCode = $address->postalCode;
        $this->addressCountry = $address->country;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        if (null === $this->id) {
            throw new LogicException('A persisted customer is required.');
        }

        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEmail(): Email
    {
        return new Email($this->email);
    }

    public function getAddress(): ?Address
    {
        if (null === $this->addressStreet && null === $this->addressCity && null === $this->addressPostalCode && null === $this->addressCountry) {
            return null;
        }

        if (null === $this->addressStreet || null === $this->addressCity || null === $this->addressPostalCode || null === $this->addressCountry) {
            throw new LogicException('Customer address is incomplete.');
        }

        return new Address($this->addressStreet, $this->addressCity, $this->addressPostalCode, $this->addressCountry);
    }
}
