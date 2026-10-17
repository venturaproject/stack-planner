<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence\Doctrine;

use App\Domain\Product\Entity\Product;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\DatabaseKernelTestCase;
use DateTimeImmutable;
use ReflectionProperty;

final class DoctrineProductRepositoryTest extends DatabaseKernelTestCase
{
    private ProductRepositoryInterface $products;

    protected function setUp(): void
    {
        parent::setUp();
        $this->products = self::getContainer()->get(ProductRepositoryInterface::class);
    }

    private function seed(string $sku, string $name, int $stock): Product
    {
        $product = Product::create(new Sku($sku), $name, null, Money::fromMinorUnits(1000, Currency::USD), $stock);
        $this->products->save($product);

        return $product;
    }

    public function testFindByIdsReturnsOnlyMatchingProducts(): void
    {
        $a = $this->seed('TSH-0001', 'T-Shirt', 10);
        $this->seed('MUG-0001', 'Mug', 10);

        $result = $this->products->findByIds([$a->getId()]);

        self::assertCount(1, $result);
        self::assertSame('TSH-0001', $result[0]->getSku()->value());
    }

    public function testFindByIdsReturnsEmptyArrayForEmptyInput(): void
    {
        self::assertSame([], $this->products->findByIds([]));
    }

    public function testFindForExportFiltersBySearch(): void
    {
        $this->seed('TSH-0001', 'Classic T-Shirt', 10);
        $this->seed('MUG-0001', 'Ceramic Mug', 10);

        $result = $this->products->findForExport('shirt', null, 100);

        self::assertCount(1, $result);
        self::assertSame('TSH-0001', $result[0]->getSku()->value());
    }

    public function testFindForExportFiltersByLowStockLevel(): void
    {
        $this->seed('TSH-0001', 'Low stock', 2);
        $this->seed('MUG-0001', 'Healthy stock', 50);

        $result = $this->products->findForExport(null, 'low', 100);

        self::assertCount(1, $result);
        self::assertSame('TSH-0001', $result[0]->getSku()->value());
    }

    public function testFindForExportFiltersByOutOfStockLevel(): void
    {
        $this->seed('TSH-0001', 'Out of stock', 0);
        $this->seed('MUG-0001', 'In stock', 5);

        $result = $this->products->findForExport(null, 'out', 100);

        self::assertCount(1, $result);
        self::assertSame('TSH-0001', $result[0]->getSku()->value());
    }

    public function testCountLowStockCountsAtOrBelowThreshold(): void
    {
        $this->seed('TSH-0001', 'Low', 3);
        $this->seed('MUG-0001', 'High', 50);

        self::assertSame(1, $this->products->countLowStock(5));
    }

    public function testFindPaginatedAndCountAllRespectSearch(): void
    {
        $this->seed('TSH-0001', 'Classic T-Shirt', 10);
        $this->seed('MUG-0001', 'Ceramic Mug', 10);

        self::assertCount(1, $this->products->findPaginated(1, 20, 'mug'));
        self::assertSame(1, $this->products->countAll('mug'));
        self::assertSame(2, $this->products->countAll(null));
    }

    public function testFindBySkuReturnsNullWhenNotFound(): void
    {
        self::assertNull($this->products->findBySku(new Sku('MIS-0001')));
    }

    public function testFindPaginatedAndCountAllRespectCreatedDateRange(): void
    {
        $this->seed('TSH-0001', 'T-Shirt', 10);

        $tomorrow = new DateTimeImmutable('+1 day');
        $yesterday = new DateTimeImmutable('-1 day');

        self::assertCount(1, $this->products->findPaginated(1, 20, null, null, $yesterday, $tomorrow));
        self::assertSame(1, $this->products->countAll(null, null, $yesterday, $tomorrow));

        self::assertCount(0, $this->products->findPaginated(1, 20, null, null, $tomorrow));
        self::assertSame(0, $this->products->countAll(null, null, $tomorrow));
    }

    public function testFindPaginatedRespectsStockStatusFilter(): void
    {
        $this->seed('TSH-0001', 'In stock', 10);
        $zero = $this->seed('MUG-0001', 'Zero stock', 0);

        $negative = Product::create(new Sku('CAP-0001'), 'Negative stock', null, Money::fromMinorUnits(1000, Currency::USD), 0);
        (new ReflectionProperty($negative, 'stockQuantity'))->setValue($negative, -3);
        $this->products->save($negative);

        self::assertCount(1, $this->products->findPaginated(1, 20, null, null, null, null, 'positive'));
        self::assertCount(1, $this->products->findPaginated(1, 20, null, null, null, null, 'zero'));
        self::assertSame($zero->getId(), $this->products->findPaginated(1, 20, null, null, null, null, 'zero')[0]->getId());
        self::assertCount(1, $this->products->findPaginated(1, 20, null, null, null, null, 'negative'));
        self::assertSame($negative->getId(), $this->products->findPaginated(1, 20, null, null, null, null, 'negative')[0]->getId());
    }

    public function testFindPaginatedAndCountAllRespectPriceRange(): void
    {
        $cheap = Product::create(new Sku('STK-0001'), 'Cheap', null, Money::fromMinorUnits(500, Currency::USD), 10);
        $this->products->save($cheap);
        $expensive = Product::create(new Sku('BPK-0001'), 'Expensive', null, Money::fromMinorUnits(5000, Currency::USD), 10);
        $this->products->save($expensive);

        $result = $this->products->findPaginated(1, 20, null, null, null, null, null, 1000, 6000);

        self::assertCount(1, $result);
        self::assertSame($expensive->getId(), $result[0]->getId());
        self::assertSame(1, $this->products->countAll(null, null, null, null, null, 1000, 6000));
    }
}
