<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Order\Command;

use App\Application\Order\Command\UpdateOrderLineQuantityCommand;
use App\Application\Order\Command\UpdateOrderLineQuantityHandler;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\Exception\OrderLineNotFoundException;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class UpdateOrderLineQuantityHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testUpdatesLineQuantityAndTotal(): void
    {
        $order = Order::startDraft(1, null);
        $order->addLine(1, new Sku('TSH-0001'), 'T-Shirt', Money::fromMinorUnits(1000, Currency::USD), 2);
        self::setId($order, 1);
        self::setId($order->getLines()[0], 10);

        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn($order);
        $orders->method('save')->willReturnArgument(0);

        $handler = new UpdateOrderLineQuantityHandler($orders);

        $response = $handler->handle(new UpdateOrderLineQuantityCommand(1, 10, 5));

        self::assertSame(5, $response->lines[0]->quantity);
        self::assertSame(5000, $response->totalAmountMinorUnits);
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn(null);

        $handler = new UpdateOrderLineQuantityHandler($orders);

        $this->expectException(OrderNotFoundException::class);

        $handler->handle(new UpdateOrderLineQuantityCommand(999, 10, 5));
    }

    public function testThrowsWhenLineNotFound(): void
    {
        $order = Order::startDraft(1, null);
        $order->addLine(1, new Sku('TSH-0001'), 'T-Shirt', Money::fromMinorUnits(1000, Currency::USD), 2);
        self::setId($order, 1);
        self::setId($order->getLines()[0], 10);

        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn($order);

        $handler = new UpdateOrderLineQuantityHandler($orders);

        $this->expectException(OrderLineNotFoundException::class);

        $handler->handle(new UpdateOrderLineQuantityCommand(1, 999, 5));
    }
}
