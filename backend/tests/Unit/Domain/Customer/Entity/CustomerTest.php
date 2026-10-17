<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Customer\Entity;

use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\Event\CustomerAddressChanged;
use App\Domain\Customer\Event\CustomerRegistered;
use App\Domain\Customer\ValueObject\Address;
use App\Domain\Customer\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class CustomerTest extends TestCase
{
    public function testRegisterRaisesCustomerRegistered(): void
    {
        $customer = Customer::register('Ada Lovelace', new Email('Ada@Example.com'));

        $events = $customer->pullEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(CustomerRegistered::class, $events[0]);
        self::assertSame('ada@example.com', $customer->getEmail()->value());
    }

    public function testRegisterWithoutAddressLeavesAddressNull(): void
    {
        $customer = Customer::register('Ada Lovelace', new Email('ada@example.com'));

        self::assertNull($customer->getAddress());
    }

    public function testRegisterWithAddressStoresIt(): void
    {
        $address = new Address('221B Baker St', 'London', 'NW1', 'UK');
        $customer = Customer::register('Sherlock Holmes', new Email('sherlock@example.com'), $address);

        self::assertTrue($address->equals($customer->getAddress()));
    }

    public function testChangeAddressRaisesCustomerAddressChanged(): void
    {
        $customer = Customer::register('Ada Lovelace', new Email('ada@example.com'));
        $customer->pullEvents();

        $newAddress = new Address('10 Downing St', 'London', 'SW1A', 'UK');
        $customer->changeAddress($newAddress);

        $events = $customer->pullEvents();
        self::assertInstanceOf(CustomerAddressChanged::class, $events[0]);
        self::assertTrue($newAddress->equals($customer->getAddress()));
    }
}
