<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence\Doctrine;

use App\Domain\Order\Entity\Order;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\DatabaseKernelTestCase;
use DateTimeImmutable;

final class DoctrineOrderRepositoryTest extends DatabaseKernelTestCase
{
    private OrderRepositoryInterface $orders;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orders = self::getContainer()->get(OrderRepositoryInterface::class);
    }

    private function seedDraft(int $customerId, string $sku = 'TSH-0001'): Order
    {
        $order = Order::startDraft($customerId, null);
        $order->addLine(1, new Sku($sku), 'Product', Money::fromMinorUnits(1000, Currency::USD), 1);
        $this->orders->save($order);

        return $order;
    }

    public function testFindByIdsReturnsOnlyMatchingOrders(): void
    {
        $a = $this->seedDraft(1);
        $this->seedDraft(2);

        $result = $this->orders->findByIds([$a->getId()]);

        self::assertCount(1, $result);
        self::assertSame($a->getId(), $result[0]->getId());
    }

    public function testFindByIdsReturnsEmptyArrayForEmptyInput(): void
    {
        self::assertSame([], $this->orders->findByIds([]));
    }

    public function testFindForExportFiltersByCustomerAndStatus(): void
    {
        $order = $this->seedDraft(1);
        $this->seedDraft(2);

        $result = $this->orders->findForExport(1, OrderStatus::Draft, 100);

        self::assertCount(1, $result);
        self::assertSame($order->getId(), $result[0]->getId());
    }

    public function testCountByStatus(): void
    {
        $placed = $this->seedDraft(1);
        $placed->place();
        $this->orders->save($placed);

        $this->seedDraft(2);

        self::assertSame(1, $this->orders->countByStatus(OrderStatus::Placed));
        self::assertSame(1, $this->orders->countByStatus(OrderStatus::Draft));
        self::assertSame(0, $this->orders->countByStatus(OrderStatus::Cancelled));
    }

    public function testFindPaginatedAndCountAllRespectCustomerFilter(): void
    {
        $this->seedDraft(1);
        $this->seedDraft(1);
        $this->seedDraft(2);

        self::assertCount(2, $this->orders->findPaginated(1, 20, 1));
        self::assertSame(2, $this->orders->countAll(1));
        self::assertSame(3, $this->orders->countAll(null));
    }

    public function testFindPaginatedAndCountAllRespectCreatedDateRange(): void
    {
        $this->seedDraft(1);

        $tomorrow = new DateTimeImmutable('+1 day');
        $yesterday = new DateTimeImmutable('-1 day');

        self::assertCount(1, $this->orders->findPaginated(1, 20, null, null, $yesterday, $tomorrow));
        self::assertSame(1, $this->orders->countAll(null, null, $yesterday, $tomorrow));

        self::assertCount(0, $this->orders->findPaginated(1, 20, null, null, $tomorrow));
        self::assertSame(0, $this->orders->countAll(null, null, $tomorrow));
    }
}
