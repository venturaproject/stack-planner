<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Order\Query;

use App\Application\Order\Query\GetOrderByIdHandler;
use App\Application\Order\Query\GetOrderByIdQuery;
use App\Application\Order\Query\OrderReadModelInterface;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class GetOrderByIdHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsOrderById(): void
    {
        $order = Order::startDraft(1, null);
        self::setId($order, 1);

        $orders = $this->createStub(OrderReadModelInterface::class);
        $orders->method('findById')->willReturn($order);

        $handler = new GetOrderByIdHandler($orders);

        $response = $handler->handle(new GetOrderByIdQuery(1));

        self::assertSame(1, $response->id);
        self::assertSame(1, $response->customerId);
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $orders = $this->createStub(OrderReadModelInterface::class);
        $orders->method('findById')->willReturn(null);

        $handler = new GetOrderByIdHandler($orders);

        $this->expectException(OrderNotFoundException::class);

        $handler->handle(new GetOrderByIdQuery(999));
    }
}
