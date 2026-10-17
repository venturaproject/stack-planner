<?php

declare(strict_types=1);

namespace App\Application\AuditLog\Query;

final class ListAuditLogByUserQuery
{
    public function __construct(
        public readonly string $userId,
        public readonly int $page = 1,
        public readonly int $size = 20,
    ) {
    }
}
