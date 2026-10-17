<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Customer\Command;

use App\Application\Customer\Command\UpdateCustomerAddressCommand;
use App\Application\Customer\Command\UpdateCustomerAddressHandler;
use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\Exception\CustomerNotFoundException;
use App\Domain\Customer\Repository\CustomerRepositoryInterface;
use App\Domain\Customer\ValueObject\Email;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class UpdateCustomerAddressHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testUpdatesAddress(): void
    {
        $customer = Customer::register('Ada Lovelace', new Email('ada@example.com'));
        self::setId($customer, 1);

        $customers = $this->createStub(CustomerRepositoryInterface::class);
        $customers->method('findById')->willReturn($customer);
        $customers->method('save')->willReturnArgument(0);

        $cmd = new UpdateCustomerAddressCommand('221B Baker St', 'London', 'NW1', 'UK');
        $cmd->id = 1;

        $handler = new UpdateCustomerAddressHandler($customers);
        $response = $handler->handle($cmd);

        self::assertSame('221B Baker St', $response->addressStreet);
        self::assertSame('UK', $response->addressCountry);
    }

    public function testThrowsWhenCustomerNotFound(): void
    {
        $customers = $this->createStub(CustomerRepositoryInterface::class);
        $customers->method('findById')->willReturn(null);

        $cmd = new UpdateCustomerAddressCommand('221B Baker St', 'London', 'NW1', 'UK');
        $cmd->id = 999;

        $handler = new UpdateCustomerAddressHandler($customers);

        $this->expectException(CustomerNotFoundException::class);

        $handler->handle($cmd);
    }
}
