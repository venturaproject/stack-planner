<?php

declare(strict_types=1);

namespace App\Application\Order\Query;

use App\Application\Order\DTO\OrderResponse;
use App\Application\Shared\DTO\PagedResponse;
use App\Domain\Order\ValueObject\OrderStatus;

final class ListOrdersHandler
{
    public function __construct(private readonly OrderReadModelInterface $orders)
    {
    }

    /** @return PagedResponse<OrderResponse> */
    public function handle(ListOrdersQuery $query): PagedResponse
    {
        $statusFilter = null !== $query->status ? OrderStatus::from($query->status) : null;

        $items = $this->orders->findPaginated($query->page, $query->size, $query->customerId, $statusFilter, $query->createdFrom, $query->createdTo);
        $total = $this->orders->countAll($query->customerId, $statusFilter, $query->createdFrom, $query->createdTo);

        $stats = [
            'total' => $this->orders->countAll(null),
            'draft' => $this->orders->countByStatus(OrderStatus::Draft),
            'placed' => $this->orders->countByStatus(OrderStatus::Placed),
            'cancelled' => $this->orders->countByStatus(OrderStatus::Cancelled),
        ];

        return PagedResponse::of($items, $query->page, $query->size, $total, OrderResponse::from(...), $stats);
    }
}
