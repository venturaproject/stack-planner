<?php

declare(strict_types=1);

namespace App\Application\Product\Query;

use App\Application\Product\DTO\ProductResponse;
use App\Domain\Product\Exception\ProductNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class GetProductByIdHandler
{
    public function __construct(private readonly ProductReadModelInterface $products)
    {
    }

    public function handle(GetProductByIdQuery $query): ProductResponse
    {
        $product = $this->products->findById($query->id);
        if (null === $product) {
            throw new ProductNotFoundException();
        }

        return ProductResponse::from($product);
    }
}
