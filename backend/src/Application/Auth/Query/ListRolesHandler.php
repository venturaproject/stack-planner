<?php

declare(strict_types=1);

namespace App\Application\Auth\Query;

use App\Application\Auth\DTO\RoleResponse;
use App\Application\Shared\DTO\PagedResponse;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class ListRolesHandler
{
    public function __construct(private readonly AuthReadModelInterface $roles)
    {
    }

    /** @return PagedResponse<RoleResponse> */
    public function handle(ListRolesQuery $query): PagedResponse
    {
        $items = $this->roles->findRolesPaginated($query->page, $query->size);
        $total = $this->roles->countRoles();

        return PagedResponse::of($items, $query->page, $query->size, $total, RoleResponse::from(...));
    }
}
