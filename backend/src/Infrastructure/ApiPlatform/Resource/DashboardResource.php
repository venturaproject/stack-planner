<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Application\Dashboard\DTO\DashboardResponse;
use App\Infrastructure\ApiPlatform\Provider\DashboardProvider;

#[ApiResource(
    shortName: 'Dashboard',
    operations: [
        new Get(uriTemplate: '/dashboard', provider: DashboardProvider::class, security: "is_granted('ROLE_SCOPE_DASHBOARD_READ')"),
    ],
)]
final class DashboardResource
{
    #[ApiProperty(identifier: true, readable: false, writable: false)]
    public string $id = 'dashboard';

    public int $totalProducts = 0;
    public int $lowStockProducts = 0;
    public int $totalCustomers = 0;
    public int $totalOrders = 0;
    public int $draftOrders = 0;
    public int $placedOrders = 0;
    public int $cancelledOrders = 0;

    public static function fromResponse(DashboardResponse $r): self
    {
        $resource = new self();
        $resource->totalProducts = $r->totalProducts;
        $resource->lowStockProducts = $r->lowStockProducts;
        $resource->totalCustomers = $r->totalCustomers;
        $resource->totalOrders = $r->totalOrders;
        $resource->draftOrders = $r->draftOrders;
        $resource->placedOrders = $r->placedOrders;
        $resource->cancelledOrders = $r->cancelledOrders;

        return $resource;
    }
}
