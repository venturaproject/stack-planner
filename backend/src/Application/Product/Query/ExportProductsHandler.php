<?php

declare(strict_types=1);

namespace App\Application\Product\Query;

use App\Application\Shared\DTO\TabularExport;

final class ExportProductsHandler
{
    private const MAX_ROWS = 10000;

    public function __construct(private readonly ProductReadModelInterface $products)
    {
    }

    public function handle(ExportProductsQuery $query): TabularExport
    {
        $items = $query->all
            ? $this->products->findForExport($query->search, $query->stockLevel, self::MAX_ROWS + 1, $query->createdFrom, $query->createdTo, $query->stockStatus, $query->priceMin, $query->priceMax)
            : $this->products->findByIds($query->ids);

        $limitExceeded = \count($items) > self::MAX_ROWS;
        $items = \array_slice($items, 0, self::MAX_ROWS);

        return new TabularExport(array_map(static fn ($product) => [
            $product->getSku()->value(), $product->getName(), $product->getDescription() ?? '',
            number_format($product->getPrice()->amountMinorUnits() / 100, 2), $product->getPrice()->currency()->value,
            $product->getStockQuantity(),
        ], $items), $limitExceeded);
    }
}
