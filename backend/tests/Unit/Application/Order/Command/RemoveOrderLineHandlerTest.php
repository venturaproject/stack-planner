<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Order\Command;

use App\Application\Order\Command\RemoveOrderLineCommand;
use App\Application\Order\Command\RemoveOrderLineHandler;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\Exception\OrderLineNotFoundException;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class RemoveOrderLineHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testRemovesLineAndRecalculatesTotal(): void
    {
        $order = Order::startDraft(1, null);
        $order->addLine(1, new Sku('TSH-0001'), 'T-Shirt', Money::fromMinorUnits(1000, Currency::USD), 2);
        $order->addLine(2, new Sku('MUG-0001'), 'Mug', Money::fromMinorUnits(500, Currency::USD), 1);
        self::setId($order, 1);
        self::setId($order->getLines()[0], 10);
        self::setId($order->getLines()[1], 11);

        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn($order);
        $orders->method('save')->willReturnArgument(0);

        $handler = new RemoveOrderLineHandler($orders);

        $response = $handler->handle(new RemoveOrderLineCommand(1, 10));

        self::assertCount(1, $response->lines);
        self::assertSame(500, $response->totalAmountMinorUnits);
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn(null);

        $handler = new RemoveOrderLineHandler($orders);

        $this->expectException(OrderNotFoundException::class);

        $handler->handle(new RemoveOrderLineCommand(999, 10));
    }

    public function testThrowsWhenLineNotFound(): void
    {
        $order = Order::startDraft(1, null);
        $order->addLine(1, new Sku('TSH-0001'), 'T-Shirt', Money::fromMinorUnits(1000, Currency::USD), 2);
        self::setId($order, 1);
        self::setId($order->getLines()[0], 10);

        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn($order);

        $handler = new RemoveOrderLineHandler($orders);

        $this->expectException(OrderLineNotFoundException::class);

        $handler->handle(new RemoveOrderLineCommand(1, 999));
    }
}
