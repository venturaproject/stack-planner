<?php

declare(strict_types=1);

namespace App\Application\Product\Command;

use App\Application\Product\DTO\ProductResponse;
use App\Application\Shared\Storage\FileStorageInterface;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class RemoveProductImageHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly FileStorageInterface $storage,
    ) {
    }

    public function handle(RemoveProductImageCommand $cmd): ProductResponse
    {
        $product = $this->products->findById($cmd->id)
            ?? throw new ProductNotFoundException();

        $filename = $product->getImageFilename();
        if (null !== $filename) {
            $this->storage->delete('products', $filename);
        }

        $product->removeImage();

        return ProductResponse::from($this->products->save($product));
    }
}
