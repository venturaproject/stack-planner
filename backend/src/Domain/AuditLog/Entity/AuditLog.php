<?php

declare(strict_types=1);

namespace App\Domain\AuditLog\Entity;

use DateTimeImmutable;

class AuditLog
{
    // Assigned by a real Postgres sequence on insert (see the mapping file and
    // migration) — never set here, Doctrine populates it via reflection after
    // save() refetches it (the column is NOT NULL, so nullable typing here would
    // be inaccurate — left genuinely uninitialized instead of defaulted, since
    // there's no meaningful value before that point). int|string per Doctrine's
    // own convention for its 'bigint' type: PHP ints can't represent the full
    // bigint range on a 32-bit build, so the DBAL type hydrates as a numeric
    // string in that case, an int otherwise.
    private int|string $chainSequence;

    public function __construct(
        private string $id,
        private ?string $actorId,
        private ?string $actorEmail,
        private string $action,
        private string $resourceType,
        private ?string $resourceId,
        /** @var array<string, mixed> */
        private array $metadata,
        private ?string $ipAddress,
        private string $previousHash,
        private string $hash,
        private DateTimeImmutable $createdAt,
        // Id of the outbox_events row this entry was recorded from, when it came through
        // the outbox (see AuditLogRecorder). Null for any entry not sourced from an event.
        // Unique so a redelivered event (the outbox only guarantees at-least-once) can't
        // produce a duplicate row.
        private ?string $outboxEventId = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getActorId(): ?string
    {
        return $this->actorId;
    }

    public function getActorEmail(): ?string
    {
        return $this->actorEmail;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    /** @return array<string, mixed> */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getPreviousHash(): string
    {
        return $this->previousHash;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getOutboxEventId(): ?string
    {
        return $this->outboxEventId;
    }

    public function getChainSequence(): int|string
    {
        return $this->chainSequence;
    }
}
