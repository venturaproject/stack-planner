<?php

declare(strict_types=1);

namespace App\Application\Product\DTO;

use App\Domain\Product\Entity\Product;
use DateTimeInterface;

final readonly class ProductResponse
{
    public function __construct(
        public int $id,
        public string $sku,
        public string $name,
        public ?string $description,
        public int $priceAmountMinorUnits,
        public string $priceCurrency,
        public int $stockQuantity,
        public ?string $imageFilename,
        public string $createdAt,
    ) {
    }

    public static function from(Product $product): self
    {
        return new self(
            id: $product->requireId(),
            sku: $product->getSku()->value(),
            name: $product->getName(),
            description: $product->getDescription(),
            priceAmountMinorUnits: $product->getPrice()->amountMinorUnits(),
            priceCurrency: $product->getPrice()->currency()->value,
            stockQuantity: $product->getStockQuantity(),
            imageFilename: $product->getImageFilename(),
            createdAt: $product->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
