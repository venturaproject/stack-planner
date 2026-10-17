<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Customer\Command;

use App\Application\Customer\Command\RegisterCustomerCommand;
use App\Application\Customer\Command\RegisterCustomerHandler;
use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\Exception\EmailAlreadyRegisteredException;
use App\Domain\Customer\Repository\CustomerRepositoryInterface;
use App\Domain\Customer\ValueObject\Email;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class RegisterCustomerHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testRegistersCustomerWithoutAddress(): void
    {
        $customers = $this->createStub(CustomerRepositoryInterface::class);
        $customers->method('findByEmail')->willReturn(null);
        $customers->method('save')->willReturnCallback(static function (Customer $customer) {
            self::setId($customer, 1);

            return $customer;
        });

        $handler = new RegisterCustomerHandler($customers);

        $response = $handler->handle(new RegisterCustomerCommand('Ada Lovelace', 'ada@example.com'));

        self::assertSame(1, $response->id);
        self::assertSame('ada@example.com', $response->email);
        self::assertNull($response->addressStreet);
    }

    public function testRegistersCustomerWithAddress(): void
    {
        $customers = $this->createStub(CustomerRepositoryInterface::class);
        $customers->method('findByEmail')->willReturn(null);
        $customers->method('save')->willReturnCallback(static function (Customer $customer) {
            self::setId($customer, 1);

            return $customer;
        });

        $handler = new RegisterCustomerHandler($customers);

        $response = $handler->handle(new RegisterCustomerCommand(
            name: 'Ada Lovelace',
            email: 'ada@example.com',
            addressStreet: '221B Baker St',
            addressCity: 'London',
            addressPostalCode: 'NW1',
            addressCountry: 'UK',
        ));

        self::assertSame('221B Baker St', $response->addressStreet);
        self::assertSame('London', $response->addressCity);
    }

    public function testThrowsWhenEmailAlreadyRegistered(): void
    {
        $customers = $this->createMock(CustomerRepositoryInterface::class);
        $customers->method('findByEmail')->willReturn(Customer::register('Existing', new Email('ada@example.com')));
        $customers->expects(self::never())->method('save');

        $handler = new RegisterCustomerHandler($customers);

        $this->expectException(EmailAlreadyRegisteredException::class);

        $handler->handle(new RegisterCustomerCommand('Ada Lovelace', 'ada@example.com'));
    }
}
