<?php

declare(strict_types=1);

namespace App\Application\Product\Command;

use App\Application\Product\DTO\ProductResponse;
use App\Application\Shared\Storage\FileStorageInterface;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class UploadProductImageHandler
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly FileStorageInterface $storage,
    ) {
    }

    public function handle(UploadProductImageCommand $cmd): ProductResponse
    {
        $ext = strtolower(ltrim($cmd->extension, '.'));
        if (!\in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Invalid file type. Allowed: jpg, jpeg, png, gif, webp');
        }

        $product = $this->products->findById($cmd->id)
            ?? throw new ProductNotFoundException();

        $filename = $cmd->id.'.'.$ext;
        $previous = $product->getImageFilename();
        $this->storage->store('products', $filename, $cmd->tmpPath);

        if (null !== $previous && $previous !== $filename) {
            $this->storage->delete('products', $previous);
        }

        $product->attachImage($filename);

        return ProductResponse::from($this->products->save($product));
    }
}
