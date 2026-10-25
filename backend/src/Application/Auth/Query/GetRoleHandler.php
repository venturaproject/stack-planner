<?php

declare(strict_types=1);

namespace App\Application\Auth\Query;

use App\Application\Auth\DTO\RoleResponse;
use App\Domain\Auth\Exception\RoleNotFoundException;

final class GetRoleHandler
{
    public function __construct(private readonly AuthReadModelInterface $roles)
    {
    }

    public function handle(GetRoleQuery $query): RoleResponse
    {
        return RoleResponse::from(
            $this->roles->findRoleById($query->id) ?? throw new RoleNotFoundException()
        );
    }
}
