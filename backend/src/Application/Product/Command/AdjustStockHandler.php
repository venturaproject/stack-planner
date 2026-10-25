<?php

declare(strict_types=1);

namespace App\Application\Product\Command;

use App\Application\Product\DTO\ProductResponse;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use InvalidArgumentException;

final class AdjustStockHandler
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    public function handle(AdjustStockCommand $cmd): ProductResponse
    {
        if (0 === $cmd->delta) {
            throw new InvalidArgumentException('Stock adjustment delta cannot be zero');
        }

        $product = $this->products->findById($cmd->id);
        if (null === $product) {
            throw new ProductNotFoundException();
        }

        if ($cmd->delta > 0) {
            $product->restock($cmd->delta, $cmd->reason);
        } else {
            $product->reserveStock(-$cmd->delta, $cmd->reason);
        }

        $this->products->save($product);

        return ProductResponse::from($product);
    }
}
