<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Order\Command;

use App\Application\Order\Command\PlaceOrderCommand;
use App\Application\Order\Command\PlaceOrderHandler;
use App\Application\Shared\Transaction\TransactionManagerInterface;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class PlaceOrderHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testPlacesOrderAndReservesStockForEachLine(): void
    {
        $product = Product::create(new Sku('TSH-0001'), 'T-Shirt', null, Money::fromMinorUnits(1000, Currency::USD), 10);
        self::setId($product, 1);

        $order = Order::startDraft(1, null);
        $order->addLine(1, new Sku('TSH-0001'), 'T-Shirt', Money::fromMinorUnits(1000, Currency::USD), 3);
        self::setId($order, 1);
        self::setId($order->getLines()[0], 1);

        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findByIdForUpdate')->willReturn($order);

        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->method('findByIdForUpdate')->willReturn($product);
        $products->expects(self::never())->method('save');

        $handler = new PlaceOrderHandler($orders, $products, $this->transactions());

        $response = $handler->handle(new PlaceOrderCommand(1));

        self::assertSame(OrderStatus::Placed->value, $response->status);
        self::assertSame(7, $product->getStockQuantity());
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findByIdForUpdate')->willReturn(null);

        $handler = new PlaceOrderHandler($orders, $this->createStub(ProductRepositoryInterface::class), $this->transactions());

        $this->expectException(OrderNotFoundException::class);

        $handler->handle(new PlaceOrderCommand(999));
    }

    public function testThrowsWhenLineProductNoLongerExists(): void
    {
        $order = Order::startDraft(1, null);
        $order->addLine(1, new Sku('TSH-0001'), 'T-Shirt', Money::fromMinorUnits(1000, Currency::USD), 3);
        self::setId($order, 1);

        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findByIdForUpdate')->willReturn($order);

        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findByIdForUpdate')->willReturn(null);

        $handler = new PlaceOrderHandler($orders, $products, $this->transactions());

        $this->expectException(ProductNotFoundException::class);

        $handler->handle(new PlaceOrderCommand(1));
    }

    private function transactions(): TransactionManagerInterface
    {
        return new class implements TransactionManagerInterface {
            public function run(callable $operation): mixed
            {
                return $operation();
            }
        };
    }
}
