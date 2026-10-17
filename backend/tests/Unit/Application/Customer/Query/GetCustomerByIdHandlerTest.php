<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Customer\Query;

use App\Application\Customer\Query\CustomerReadModelInterface;
use App\Application\Customer\Query\GetCustomerByIdHandler;
use App\Application\Customer\Query\GetCustomerByIdQuery;
use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\Exception\CustomerNotFoundException;
use App\Domain\Customer\ValueObject\Email;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class GetCustomerByIdHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsCustomerById(): void
    {
        $customer = Customer::register('Ada Lovelace', new Email('ada@example.com'));
        self::setId($customer, 1);

        $customers = $this->createStub(CustomerReadModelInterface::class);
        $customers->method('findById')->willReturn($customer);

        $handler = new GetCustomerByIdHandler($customers);

        $response = $handler->handle(new GetCustomerByIdQuery(1));

        self::assertSame(1, $response->id);
        self::assertSame('ada@example.com', $response->email);
    }

    public function testThrowsWhenCustomerNotFound(): void
    {
        $customers = $this->createStub(CustomerReadModelInterface::class);
        $customers->method('findById')->willReturn(null);

        $handler = new GetCustomerByIdHandler($customers);

        $this->expectException(CustomerNotFoundException::class);

        $handler->handle(new GetCustomerByIdQuery(999));
    }
}
