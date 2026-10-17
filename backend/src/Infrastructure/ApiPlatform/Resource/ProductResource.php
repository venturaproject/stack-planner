<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Query;
use App\Application\Product\DTO\ProductResponse;
use App\Infrastructure\ApiPlatform\Input\ProductSearchCriteria;
use App\Infrastructure\ApiPlatform\Processor\AdjustStockProcessor;
use App\Infrastructure\ApiPlatform\Processor\CreateProductProcessor;
use App\Infrastructure\ApiPlatform\Processor\ProductSearchProcessor;
use App\Infrastructure\ApiPlatform\Processor\UpdateProductProcessor;
use App\Infrastructure\ApiPlatform\Provider\ProductProvider;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Product',
    operations: [
        new GetCollection(uriTemplate: '/products', provider: ProductProvider::class, security: "is_granted('ROLE_SCOPE_PRODUCTS_READ')"),
        new Query(
            uriTemplate: '/products/search',
            input: ProductSearchCriteria::class,
            read: false,
            deserialize: true,
            validate: true,
            write: true,
            processor: ProductSearchProcessor::class,
            security: "is_granted('ROLE_SCOPE_PRODUCTS_READ')",
            description: 'Safe, idempotent product search with criteria supplied in the QUERY request body.',
        ),
        new Get(uriTemplate: '/products/{id}', provider: ProductProvider::class, security: "is_granted('ROLE_SCOPE_PRODUCTS_READ')"),
        new Post(uriTemplate: '/products', processor: CreateProductProcessor::class, security: "is_granted('ROLE_SCOPE_PRODUCTS_WRITE')"),
        new Patch(uriTemplate: '/products/{id}', provider: ProductProvider::class, processor: UpdateProductProcessor::class, security: "is_granted('ROLE_SCOPE_PRODUCTS_WRITE')"),
        new Post(
            uriTemplate: '/products/{id}/stock-adjustments',
            provider: ProductProvider::class,
            processor: AdjustStockProcessor::class,
            status: 200,
            security: "is_granted('ROLE_SCOPE_PRODUCTS_WRITE')",
        ),
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 20,
    paginationClientItemsPerPage: true,
    paginationMaximumItemsPerPage: 100,
)]
final class ProductResource
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    #[Assert\NotBlank]
    public ?string $sku = null;

    #[Assert\NotBlank]
    public ?string $name = null;

    public ?string $description = null;

    public ?int $priceAmountMinorUnits = null;

    public ?string $priceCurrency = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?int $stockQuantity = null;

    public ?int $initialStock = null;

    // Stock-adjustment inputs (only used by the /stock-adjustments operation)
    public ?int $delta = null;
    public ?string $reason = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $imageFilename = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $createdAt = null;

    public static function fromResponse(ProductResponse $r): self
    {
        $resource = new self();
        $resource->id = $r->id;
        $resource->sku = $r->sku;
        $resource->name = $r->name;
        $resource->description = $r->description;
        $resource->priceAmountMinorUnits = $r->priceAmountMinorUnits;
        $resource->priceCurrency = $r->priceCurrency;
        $resource->stockQuantity = $r->stockQuantity;
        $resource->imageFilename = $r->imageFilename;
        $resource->createdAt = $r->createdAt;

        return $resource;
    }
}
