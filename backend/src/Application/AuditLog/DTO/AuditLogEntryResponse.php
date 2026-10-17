<?php

declare(strict_types=1);

namespace App\Application\AuditLog\DTO;

use App\Domain\AuditLog\Entity\AuditLog;
use DateTimeInterface;

final class AuditLogEntryResponse
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $actorId,
        public readonly ?string $actorEmail,
        public readonly string $action,
        public readonly string $resourceType,
        public readonly ?string $resourceId,
        /** @var array<string, mixed> */
        public readonly array $metadata,
        public readonly ?string $ipAddress,
        public readonly string $previousHash,
        public readonly string $hash,
        public readonly string $createdAt,
    ) {
    }

    public static function from(AuditLog $entry): self
    {
        return new self(
            id: $entry->getId(),
            actorId: $entry->getActorId(),
            actorEmail: $entry->getActorEmail(),
            action: $entry->getAction(),
            resourceType: $entry->getResourceType(),
            resourceId: $entry->getResourceId(),
            metadata: $entry->getMetadata(),
            ipAddress: $entry->getIpAddress(),
            previousHash: $entry->getPreviousHash(),
            hash: $entry->getHash(),
            createdAt: $entry->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
