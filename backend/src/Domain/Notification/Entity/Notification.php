<?php

declare(strict_types=1);

namespace App\Domain\Notification\Entity;

use App\Domain\Notification\Event\NotificationCreated;
use App\Domain\Shared\HasDomainEvents;
use App\Domain\Shared\RecordsDomainEvents;
use DateTimeImmutable;
use LogicException;

class Notification implements HasDomainEvents
{
    use RecordsDomainEvents;

    private ?string $id = null;
    private string $title;
    private string $message;
    private string $type = 'info';
    private ?DateTimeImmutable $readAt = null;
    private DateTimeImmutable $createdAt;
    private ?string $userId = null;
    private ?string $outboxEventId = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function requireId(): string
    {
        if (null === $this->id) {
            throw new LogicException('A persisted notification is required.');
        }

        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getReadAt(): ?DateTimeImmutable
    {
        return $this->readAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): void
    {
        $this->userId = $userId;
    }

    public function getOutboxEventId(): ?string
    {
        return $this->outboxEventId;
    }

    public function setOutboxEventId(?string $outboxEventId): void
    {
        $this->outboxEventId = $outboxEventId;
    }

    public function markAsRead(): void
    {
        if (null === $this->readAt) {
            $this->readAt = new DateTimeImmutable();
        }
    }

    public function isRead(): bool
    {
        return null !== $this->readAt;
    }

    public function recordCreated(): void
    {
        $this->raise(new NotificationCreated($this->id, $this->title, $this->userId));
    }
}
