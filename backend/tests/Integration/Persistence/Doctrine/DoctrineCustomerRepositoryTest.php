<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence\Doctrine;

use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\Repository\CustomerRepositoryInterface;
use App\Domain\Customer\ValueObject\Address;
use App\Domain\Customer\ValueObject\Email;
use App\Tests\Support\DatabaseKernelTestCase;
use DateTimeImmutable;

final class DoctrineCustomerRepositoryTest extends DatabaseKernelTestCase
{
    private CustomerRepositoryInterface $customers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customers = self::getContainer()->get(CustomerRepositoryInterface::class);
    }

    private function seed(string $name, string $email, ?Address $address = null): Customer
    {
        $customer = Customer::register($name, new Email($email), $address);
        $this->customers->save($customer);

        return $customer;
    }

    public function testFindByIdsReturnsOnlyMatchingCustomers(): void
    {
        $a = $this->seed('Ada Lovelace', 'ada@example.com');
        $this->seed('Grace Hopper', 'grace@example.com');

        $result = $this->customers->findByIds([$a->getId()]);

        self::assertCount(1, $result);
        self::assertSame('ada@example.com', $result[0]->getEmail()->value());
    }

    public function testFindByIdsReturnsEmptyArrayForEmptyInput(): void
    {
        self::assertSame([], $this->customers->findByIds([]));
    }

    public function testFindForExportFiltersBySearch(): void
    {
        $this->seed('Ada Lovelace', 'ada@example.com');
        $this->seed('Grace Hopper', 'grace@example.com');

        $result = $this->customers->findForExport('ada', null, 100);

        self::assertCount(1, $result);
        self::assertSame('ada@example.com', $result[0]->getEmail()->value());
    }

    public function testFindForExportFiltersByHasAddress(): void
    {
        $this->seed('Has Address', 'has@example.com', new Address('221B Baker St', 'London', 'NW1', 'UK'));
        $this->seed('No Address', 'no@example.com');

        $withAddress = $this->customers->findForExport(null, true, 100);
        $withoutAddress = $this->customers->findForExport(null, false, 100);

        self::assertCount(1, $withAddress);
        self::assertSame('has@example.com', $withAddress[0]->getEmail()->value());
        self::assertCount(1, $withoutAddress);
        self::assertSame('no@example.com', $withoutAddress[0]->getEmail()->value());
    }

    public function testCountWithAddress(): void
    {
        $this->seed('Has Address', 'has@example.com', new Address('221B Baker St', 'London', 'NW1', 'UK'));
        $this->seed('No Address', 'no@example.com');

        self::assertSame(1, $this->customers->countWithAddress());
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        self::assertNull($this->customers->findByEmail(new Email('missing@example.com')));
    }

    public function testFindPaginatedAndCountAllRespectCreatedDateRange(): void
    {
        $this->seed('Ada Lovelace', 'ada@example.com');

        $tomorrow = new DateTimeImmutable('+1 day');
        $yesterday = new DateTimeImmutable('-1 day');

        self::assertCount(1, $this->customers->findPaginated(1, 20, null, null, $yesterday, $tomorrow));
        self::assertSame(1, $this->customers->countAll(null, null, $yesterday, $tomorrow));

        self::assertCount(0, $this->customers->findPaginated(1, 20, null, null, $tomorrow));
        self::assertSame(0, $this->customers->countAll(null, null, $tomorrow));
    }
}
