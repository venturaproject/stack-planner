<?php

declare(strict_types=1);

namespace App\Application\Product\Command;

use App\Application\Product\DTO\ProductResponse;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\DuplicateSkuException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class CreateProductHandler
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    public function handle(CreateProductCommand $cmd): ProductResponse
    {
        $sku = new Sku($cmd->sku);

        if (null !== $this->products->findBySku($sku)) {
            throw new DuplicateSkuException($sku);
        }

        $product = Product::create(
            $sku,
            $cmd->name,
            $cmd->description,
            Money::fromMinorUnits($cmd->priceAmountMinorUnits, Currency::from($cmd->priceCurrency)),
            $cmd->initialStock,
        );

        $this->products->save($product);

        return ProductResponse::from($product);
    }
}
