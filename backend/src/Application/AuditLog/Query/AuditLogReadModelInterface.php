<?php

declare(strict_types=1);

namespace App\Application\AuditLog\Query;

use App\Application\AuditLog\DTO\PagedAuditLogResponse;

interface AuditLogReadModelInterface
{
    public function findByActorId(string $actorId, int $page, int $size): PagedAuditLogResponse;
}
