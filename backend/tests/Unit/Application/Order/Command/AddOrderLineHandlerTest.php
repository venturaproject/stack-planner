<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Order\Command;

use App\Application\Order\Command\AddOrderLineCommand;
use App\Application\Order\Command\AddOrderLineHandler;
use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\Exception\CustomerNotFoundException;
use App\Domain\Customer\Repository\CustomerRepositoryInterface;
use App\Domain\Customer\ValueObject\Email;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\EntityIdSetter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AddOrderLineHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function product(): Product
    {
        $product = Product::create(new Sku('TSH-0001'), 'T-Shirt', null, Money::fromMinorUnits(1000, Currency::USD), 10);
        self::setId($product, 1);

        return $product;
    }

    /**
     * Mirrors what Doctrine does on flush: assigns ids to the order and any of its
     * lines that don't have one yet (cascade persist).
     */
    private static function persist(Order $order, int $orderId): Order
    {
        if (null === $order->getId()) {
            self::setId($order, $orderId);
        }
        foreach ($order->getLines() as $index => $line) {
            if (null === $line->getId()) {
                self::setId($line, $index + 1);
            }
        }

        return $order;
    }

    public function testAddsLineToExistingOrder(): void
    {
        $order = Order::startDraft(1, null);
        self::setId($order, 1);

        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn($order);
        $orders->method('save')->willReturnCallback(static fn (Order $o) => self::persist($o, 1));

        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($this->product());

        $handler = new AddOrderLineHandler($orders, $this->createStub(CustomerRepositoryInterface::class), $products);

        $response = $handler->handle(new AddOrderLineCommand(orderId: 1, productId: 1, quantity: 2));

        self::assertCount(1, $response->lines);
        self::assertSame(2, $response->lines[0]->quantity);
    }

    public function testStartsNewDraftOrderWhenNoOrderIdGiven(): void
    {
        $customer = Customer::register('Ada Lovelace', new Email('ada@example.com'));
        self::setId($customer, 7);

        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('save')->willReturnCallback(static fn (Order $o) => self::persist($o, 1));

        $customers = $this->createStub(CustomerRepositoryInterface::class);
        $customers->method('findById')->willReturn($customer);

        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($this->product());

        $handler = new AddOrderLineHandler($orders, $customers, $products);

        $response = $handler->handle(new AddOrderLineCommand(customerId: 7, productId: 1, quantity: 1));

        self::assertSame(7, $response->customerId);
        self::assertCount(1, $response->lines);
    }

    public function testThrowsWhenNeitherOrderIdNorCustomerIdGiven(): void
    {
        $handler = new AddOrderLineHandler(
            $this->createStub(OrderRepositoryInterface::class),
            $this->createStub(CustomerRepositoryInterface::class),
            $this->createStub(ProductRepositoryInterface::class),
        );

        $this->expectException(InvalidArgumentException::class);

        $handler->handle(new AddOrderLineCommand(productId: 1));
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn(null);

        $handler = new AddOrderLineHandler($orders, $this->createStub(CustomerRepositoryInterface::class), $this->createStub(ProductRepositoryInterface::class));

        $this->expectException(OrderNotFoundException::class);

        $handler->handle(new AddOrderLineCommand(orderId: 999, productId: 1));
    }

    public function testThrowsWhenCustomerNotFound(): void
    {
        $customers = $this->createStub(CustomerRepositoryInterface::class);
        $customers->method('findById')->willReturn(null);

        $handler = new AddOrderLineHandler($this->createStub(OrderRepositoryInterface::class), $customers, $this->createStub(ProductRepositoryInterface::class));

        $this->expectException(CustomerNotFoundException::class);

        $handler->handle(new AddOrderLineCommand(customerId: 999, productId: 1));
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $order = Order::startDraft(1, null);
        self::setId($order, 1);

        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn($order);

        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn(null);

        $handler = new AddOrderLineHandler($orders, $this->createStub(CustomerRepositoryInterface::class), $products);

        $this->expectException(ProductNotFoundException::class);

        $handler->handle(new AddOrderLineCommand(orderId: 1, productId: 999));
    }
}
