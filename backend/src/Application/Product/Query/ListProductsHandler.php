<?php

declare(strict_types=1);

namespace App\Application\Product\Query;

use App\Application\Product\DTO\ProductResponse;
use App\Application\Shared\DTO\PagedResponse;

final class ListProductsHandler
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function __construct(private readonly ProductReadModelInterface $products)
    {
    }

    /** @return PagedResponse<ProductResponse> */
    public function handle(ListProductsQuery $query): PagedResponse
    {
        $items = $this->products->findPaginated($query->page, $query->size, $query->search, $query->stockLevel, $query->createdFrom, $query->createdTo, $query->stockStatus, $query->priceMin, $query->priceMax);
        $total = $this->products->countAll($query->search, $query->stockLevel, $query->createdFrom, $query->createdTo, $query->stockStatus, $query->priceMin, $query->priceMax);

        $stats = [
            'total' => $this->products->countAll(null),
            'lowStock' => $this->products->countLowStock(self::LOW_STOCK_THRESHOLD),
            'outOfStock' => $this->products->countLowStock(0),
        ];

        return PagedResponse::of($items, $query->page, $query->size, $total, ProductResponse::from(...), $stats);
    }
}
