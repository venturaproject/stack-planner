<?php

declare(strict_types=1);

namespace App\Application\Product\Command;

use App\Application\Product\DTO\ProductResponse;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class UpdateProductHandler
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    public function handle(UpdateProductCommand $cmd): ProductResponse
    {
        $product = $this->products->findById($cmd->id);
        if (null === $product) {
            throw new ProductNotFoundException();
        }

        $product->updateDetails($cmd->name, $cmd->description);

        if (null !== $cmd->priceAmountMinorUnits) {
            $currency = null !== $cmd->priceCurrency
                ? Currency::from($cmd->priceCurrency)
                : $product->getPrice()->currency();
            $product->changePrice(Money::fromMinorUnits($cmd->priceAmountMinorUnits, $currency));
        }

        $this->products->save($product);

        return ProductResponse::from($product);
    }
}
