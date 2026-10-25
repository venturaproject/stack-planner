<?php

declare(strict_types=1);

namespace App\Application\AuditLog\Query;

use App\Application\AuditLog\DTO\PagedAuditLogResponse;

final class ListAuditLogByUserHandler
{
    public function __construct(private readonly AuditLogReadModelInterface $repository)
    {
    }

    public function handle(ListAuditLogByUserQuery $query): PagedAuditLogResponse
    {
        return $this->repository->findByActorId($query->userId, $query->page, $query->size);
    }
}
