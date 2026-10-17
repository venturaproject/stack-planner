<?php

declare(strict_types=1);

namespace App\Application\Order\DTO;

use App\Domain\Order\Entity\OrderLine;

final readonly class OrderLineResponse
{
    public function __construct(
        public int $id,
        public int $productId,
        public string $productSku,
        public string $productName,
        public int $unitPriceAmountMinorUnits,
        public string $unitPriceCurrency,
        public int $quantity,
        public int $lineTotalAmountMinorUnits,
    ) {
    }

    public static function from(OrderLine $line): self
    {
        return new self(
            id: $line->requireId(),
            productId: $line->getProductId(),
            productSku: $line->getProductSku()->value(),
            productName: $line->getProductName(),
            unitPriceAmountMinorUnits: $line->getUnitPrice()->amountMinorUnits(),
            unitPriceCurrency: $line->getUnitPrice()->currency()->value,
            quantity: $line->getQuantity(),
            lineTotalAmountMinorUnits: $line->getLineTotal()->amountMinorUnits(),
        );
    }
}
