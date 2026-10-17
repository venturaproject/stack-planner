<?php

declare(strict_types=1);

namespace App\Application\AuditLog\DTO;

final class PagedAuditLogResponse
{
    public function __construct(
        /** @var AuditLogEntryResponse[] */
        public readonly array $data,
        public readonly int $total,
        public readonly int $page,
        public readonly int $lastPage,
        public readonly int $perPage,
    ) {
    }
}
