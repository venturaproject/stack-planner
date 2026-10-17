<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Application\Order\DTO\OrderResponse;
use App\Infrastructure\ApiPlatform\Processor\AddOrderLineProcessor;
use App\Infrastructure\ApiPlatform\Processor\CancelOrderProcessor;
use App\Infrastructure\ApiPlatform\Processor\PlaceOrderProcessor;
use App\Infrastructure\ApiPlatform\Processor\RemoveOrderLineProcessor;
use App\Infrastructure\ApiPlatform\Processor\UpdateOrderLineQuantityProcessor;
use App\Infrastructure\ApiPlatform\Provider\OrderProvider;

#[ApiResource(
    shortName: 'Order',
    operations: [
        new GetCollection(uriTemplate: '/orders', provider: OrderProvider::class, security: "is_granted('ROLE_SCOPE_ORDERS_READ')"),
        new Get(uriTemplate: '/orders/{id}', provider: OrderProvider::class, security: "is_granted('ROLE_SCOPE_ORDERS_READ')"),
        new Post(
            uriTemplate: '/orders/lines',
            processor: AddOrderLineProcessor::class,
            status: 201,
            security: "is_granted('ROLE_SCOPE_ORDERS_WRITE')",
        ),
        new Patch(
            uriTemplate: '/orders/{id}/lines/{lineId}',
            provider: OrderProvider::class,
            processor: UpdateOrderLineQuantityProcessor::class,
            security: "is_granted('ROLE_SCOPE_ORDERS_WRITE')",
        ),
        new Delete(
            uriTemplate: '/orders/{id}/lines/{lineId}',
            provider: OrderProvider::class,
            processor: RemoveOrderLineProcessor::class,
            status: 200,
            deserialize: false,
            security: "is_granted('ROLE_SCOPE_ORDERS_WRITE')",
        ),
        new Post(
            uriTemplate: '/orders/{id}/place',
            provider: OrderProvider::class,
            processor: PlaceOrderProcessor::class,
            status: 200,
            deserialize: false,
            security: "is_granted('ROLE_SCOPE_ORDERS_WRITE')",
        ),
        new Post(
            uriTemplate: '/orders/{id}/cancel',
            provider: OrderProvider::class,
            processor: CancelOrderProcessor::class,
            status: 200,
            deserialize: false,
            security: "is_granted('ROLE_SCOPE_ORDERS_WRITE')",
        ),
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 20,
    paginationClientItemsPerPage: true,
    paginationMaximumItemsPerPage: 100,
)]
final class OrderResource
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    public ?int $customerId = null;

    // Used only by POST /orders/lines to target an existing draft order (omit to start a new one)
    public ?int $orderId = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $status = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $shippingAddressStreet = null;
    #[ApiProperty(readable: true, writable: false)]
    public ?string $shippingAddressCity = null;
    #[ApiProperty(readable: true, writable: false)]
    public ?string $shippingAddressPostalCode = null;
    #[ApiProperty(readable: true, writable: false)]
    public ?string $shippingAddressCountry = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?int $totalAmountMinorUnits = null;
    #[ApiProperty(readable: true, writable: false)]
    public ?string $totalCurrency = null;

    /** @var mixed[] */
    #[ApiProperty(readable: true, writable: false)]
    public array $lines = [];

    // Line mutation inputs (used by /orders/lines, /orders/{id}/lines/{lineId})
    public ?int $productId = null;
    public ?int $quantity = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $createdAt = null;

    public static function fromResponse(OrderResponse $r): self
    {
        $resource = new self();
        $resource->id = $r->id;
        $resource->customerId = $r->customerId;
        $resource->status = $r->status;
        $resource->shippingAddressStreet = $r->shippingAddressStreet;
        $resource->shippingAddressCity = $r->shippingAddressCity;
        $resource->shippingAddressPostalCode = $r->shippingAddressPostalCode;
        $resource->shippingAddressCountry = $r->shippingAddressCountry;
        $resource->totalAmountMinorUnits = $r->totalAmountMinorUnits;
        $resource->totalCurrency = $r->totalCurrency;
        $resource->lines = $r->lines;
        $resource->createdAt = $r->createdAt;

        return $resource;
    }
}
