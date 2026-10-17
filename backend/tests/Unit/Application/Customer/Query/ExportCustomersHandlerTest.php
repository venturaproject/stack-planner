<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Customer\Query;

use App\Application\Customer\Query\CustomerReadModelInterface;
use App\Application\Customer\Query\ExportCustomersHandler;
use App\Application\Customer\Query\ExportCustomersQuery;
use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\ValueObject\Email;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class ExportCustomersHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function customer(int $id, string $email): Customer
    {
        $customer = Customer::register('Ada Lovelace', new Email($email));
        self::setId($customer, $id);

        return $customer;
    }

    public function testExportsAllMatchingFilters(): void
    {
        $customers = $this->createMock(CustomerReadModelInterface::class);
        $customers->expects(self::once())->method('findForExport')
            ->with('ada', null, 10001)
            ->willReturn([$this->customer(1, 'ada@example.com')]);
        $customers->expects(self::never())->method('findByIds');

        $handler = new ExportCustomersHandler($customers);

        $result = $handler->handle(new ExportCustomersQuery([], 'ada', null, true));

        self::assertFalse($result->limitExceeded);
        self::assertSame([1, 'Ada Lovelace', 'ada@example.com', '', '', '', ''], $result->rows[0]);
    }

    public function testExportsOnlySelectedIds(): void
    {
        $customers = $this->createMock(CustomerReadModelInterface::class);
        $customers->expects(self::once())->method('findByIds')->with([1, 2])
            ->willReturn([$this->customer(1, 'ada@example.com'), $this->customer(2, 'bob@example.com')]);
        $customers->expects(self::never())->method('findForExport');

        $handler = new ExportCustomersHandler($customers);

        $result = $handler->handle(new ExportCustomersQuery([1, 2], null, null, false));

        self::assertCount(2, $result->rows);
    }
}
