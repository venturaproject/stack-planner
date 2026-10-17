<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product\Entity;

use App\Domain\Product\Entity\Product;
use App\Domain\Product\Event\ProductCreated;
use App\Domain\Product\Event\ProductPriceChanged;
use App\Domain\Product\Event\StockAdjusted;
use App\Domain\Product\Exception\InsufficientStockException;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    private function createProduct(int $stock = 10): Product
    {
        return Product::create(
            new Sku('TSH-0001'),
            'T-Shirt',
            'A plain t-shirt',
            Money::fromMinorUnits(1999, Currency::USD),
            $stock,
        );
    }

    public function testCreateRaisesProductCreated(): void
    {
        $product = $this->createProduct();

        $events = $product->pullEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(ProductCreated::class, $events[0]);
    }

    public function testPullEventsClearsRecordedEvents(): void
    {
        $product = $this->createProduct();
        $product->pullEvents();

        self::assertSame([], $product->pullEvents());
    }

    public function testReserveStockDecrementsQuantity(): void
    {
        $product = $this->createProduct(10);
        $product->pullEvents();

        $product->reserveStock(3);

        self::assertSame(7, $product->getStockQuantity());
        $events = $product->pullEvents();
        self::assertInstanceOf(StockAdjusted::class, $events[0]);
        self::assertSame(-3, $events[0]->delta);
    }

    public function testReserveStockThrowsWhenExceedingAvailableStock(): void
    {
        $product = $this->createProduct(2);

        $this->expectException(InsufficientStockException::class);

        $product->reserveStock(3);
    }

    public function testReserveStockRejectsNonPositiveQuantity(): void
    {
        $product = $this->createProduct();

        $this->expectException(InvalidArgumentException::class);

        $product->reserveStock(0);
    }

    public function testRestockIncrementsQuantity(): void
    {
        $product = $this->createProduct(5);
        $product->pullEvents();

        $product->restock(4);

        self::assertSame(9, $product->getStockQuantity());
    }

    public function testChangePriceRaisesEventOnlyWhenPriceActuallyChanges(): void
    {
        $product = $this->createProduct();
        $product->pullEvents();

        $product->changePrice(Money::fromMinorUnits(1999, Currency::USD));
        self::assertSame([], $product->pullEvents());

        $product->changePrice(Money::fromMinorUnits(2499, Currency::USD));
        $events = $product->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ProductPriceChanged::class, $events[0]);
        self::assertSame(2499, $events[0]->newAmountMinorUnits);
    }

    public function testCreateRejectsNegativeInitialStock(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Product::create(new Sku('TSH-0001'), 'T-Shirt', null, Money::fromMinorUnits(1000, Currency::USD), -1);
    }

    public function testRestockRejectsNonPositiveQuantity(): void
    {
        $product = $this->createProduct();

        $this->expectException(InvalidArgumentException::class);

        $product->restock(0);
    }

    public function testUpdateDetailsChangesNameAndDescription(): void
    {
        $product = $this->createProduct();

        $product->updateDetails('New Name', 'New description');

        self::assertSame('New Name', $product->getName());
        self::assertSame('New description', $product->getDescription());
    }

    public function testUpdateDetailsKeepsExistingValuesWhenNullGiven(): void
    {
        $product = $this->createProduct();

        $product->updateDetails(null, null);

        self::assertSame('T-Shirt', $product->getName());
        self::assertSame('A plain t-shirt', $product->getDescription());
    }

    public function testAttachImageSetsFilename(): void
    {
        $product = $this->createProduct();

        $product->attachImage('1.png');

        self::assertSame('1.png', $product->getImageFilename());
    }

    public function testRemoveImageClearsFilename(): void
    {
        $product = $this->createProduct();
        $product->attachImage('1.png');

        $product->removeImage();

        self::assertNull($product->getImageFilename());
    }

    public function testGetImageFilenameIsNullByDefault(): void
    {
        $product = $this->createProduct();

        self::assertNull($product->getImageFilename());
    }
}
