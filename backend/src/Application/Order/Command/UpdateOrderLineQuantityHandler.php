<?php

declare(strict_types=1);

namespace App\Application\Order\Command;

use App\Application\Order\DTO\OrderResponse;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\Repository\OrderRepositoryInterface;

final class UpdateOrderLineQuantityHandler
{
    public function __construct(private readonly OrderRepositoryInterface $orders)
    {
    }

    public function handle(UpdateOrderLineQuantityCommand $cmd): OrderResponse
    {
        $order = $this->orders->findById($cmd->orderId)
            ?? throw new OrderNotFoundException();

        $order->changeLineQuantity($cmd->lineId, $cmd->quantity);
        $this->orders->save($order);

        return OrderResponse::from($order);
    }
}
