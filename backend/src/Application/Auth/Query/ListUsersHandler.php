<?php

declare(strict_types=1);

namespace App\Application\Auth\Query;

use App\Application\Auth\DTO\UserResponse;
use App\Application\Shared\DTO\PagedResponse;

final class ListUsersHandler
{
    public function __construct(private readonly AuthReadModelInterface $users)
    {
    }

    /** @return PagedResponse<UserResponse> */
    public function handle(ListUsersQuery $query): PagedResponse
    {
        $items = $this->users->findUsersPaginated($query->page, $query->size);
        $total = $this->users->countUsers();

        return PagedResponse::of($items, $query->page, $query->size, $total, UserResponse::from(...));
    }
}
