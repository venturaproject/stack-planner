<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Customer\Query;

use App\Application\Customer\Query\CustomerReadModelInterface;
use App\Application\Customer\Query\ListCustomersHandler;
use App\Application\Customer\Query\ListCustomersQuery;
use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\ValueObject\Email;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class ListCustomersHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsPagedCustomersWithAddressStats(): void
    {
        $customer = Customer::register('Ada Lovelace', new Email('ada@example.com'));
        self::setId($customer, 1);

        $customers = $this->createStub(CustomerReadModelInterface::class);
        $customers->method('findPaginated')->willReturn([$customer]);
        $customers->method('countAll')->willReturnMap([
            ['search', null, 1],
            [null, null, 10],
        ]);
        $customers->method('countWithAddress')->willReturn(4);

        $handler = new ListCustomersHandler($customers);

        $response = $handler->handle(new ListCustomersQuery(1, 20, 'search'));

        self::assertCount(1, $response->data);
        self::assertSame(['total' => 10, 'withAddress' => 4, 'withoutAddress' => 6], $response->stats);
    }
}
