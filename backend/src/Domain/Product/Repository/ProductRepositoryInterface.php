<?php

declare(strict_types=1);

namespace App\Domain\Product\Repository;

use App\Domain\Product\Entity\Product;
use App\Domain\Product\ValueObject\Sku;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    public function findByIdForUpdate(int $id): ?Product;

    public function findBySku(Sku $sku): ?Product;

    public function save(Product $product): Product;
}
