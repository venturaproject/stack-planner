<?php

declare(strict_types=1);

namespace App\Application\Order\Query;

use App\Application\Order\DTO\OrderResponse;
use App\Domain\Order\Exception\OrderNotFoundException;

final class GetOrderByIdHandler
{
    public function __construct(private readonly OrderReadModelInterface $orders)
    {
    }

    public function handle(GetOrderByIdQuery $query): OrderResponse
    {
        $order = $this->orders->findById($query->id);
        if (null === $order) {
            throw new OrderNotFoundException();
        }

        return OrderResponse::from($order);
    }
}
