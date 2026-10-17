<?php

declare(strict_types=1);

namespace App\Application\Auth\Query;

use App\Application\Auth\DTO\PermissionResponse;
use App\Application\Shared\DTO\PagedResponse;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class ListPermissionsHandler
{
    public function __construct(private readonly AuthReadModelInterface $permissions)
    {
    }

    /** @return PagedResponse<PermissionResponse> */
    public function handle(ListPermissionsQuery $query): PagedResponse
    {
        $items = $this->permissions->findPermissionsPaginated($query->page, $query->size);
        $total = $this->permissions->countPermissions();

        return PagedResponse::of($items, $query->page, $query->size, $total, PermissionResponse::from(...));
    }
}
