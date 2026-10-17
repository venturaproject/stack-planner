<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Order\Command;

use App\Application\Order\Command\CancelOrderCommand;
use App\Application\Order\Command\CancelOrderHandler;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\Exception\OrderNotModifiableException;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class CancelOrderHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testCancelsDraftOrder(): void
    {
        $order = Order::startDraft(1, null);
        self::setId($order, 1);

        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn($order);
        $orders->method('save')->willReturnArgument(0);

        $handler = new CancelOrderHandler($orders);

        $response = $handler->handle(new CancelOrderCommand(1));

        self::assertSame(OrderStatus::Cancelled->value, $response->status);
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn(null);

        $handler = new CancelOrderHandler($orders);

        $this->expectException(OrderNotFoundException::class);

        $handler->handle(new CancelOrderCommand(999));
    }

    public function testThrowsWhenOrderAlreadyCancelled(): void
    {
        $order = Order::startDraft(1, null);
        $order->cancel();
        self::setId($order, 1);

        $orders = $this->createStub(OrderRepositoryInterface::class);
        $orders->method('findById')->willReturn($order);

        $handler = new CancelOrderHandler($orders);

        $this->expectException(OrderNotModifiableException::class);

        $handler->handle(new CancelOrderCommand(1));
    }
}
