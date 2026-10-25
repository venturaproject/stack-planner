<?php

declare(strict_types=1);

namespace App\Application\Order\Command;

use App\Application\Order\DTO\OrderResponse;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\Repository\OrderRepositoryInterface;

final class RemoveOrderLineHandler
{
    public function __construct(private readonly OrderRepositoryInterface $orders)
    {
    }

    public function handle(RemoveOrderLineCommand $cmd): OrderResponse
    {
        $order = $this->orders->findById($cmd->orderId)
            ?? throw new OrderNotFoundException();

        $order->removeLine($cmd->lineId);
        $this->orders->save($order);

        return OrderResponse::from($order);
    }
}
