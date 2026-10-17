<?php

declare(strict_types=1);

namespace App\Application\Notification\DTO;

use App\Domain\Notification\Entity\Notification;
use DateTimeInterface;

final readonly class NotificationResponse
{
    public function __construct(
        public string $id,
        public string $title,
        public string $message,
        public string $createdAt,
        public ?string $readAt,
        public string $typeColor,
    ) {
    }

    public static function from(Notification $n): self
    {
        return new self(
            id: $n->requireId(),
            title: $n->getTitle(),
            message: $n->getMessage(),
            createdAt: $n->getCreatedAt()->format(DateTimeInterface::ATOM),
            readAt: $n->getReadAt()?->format(DateTimeInterface::ATOM),
            typeColor: $n->getType(),
        );
    }
}
