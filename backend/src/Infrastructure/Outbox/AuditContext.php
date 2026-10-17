<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Application\Shared\Outbox\AuditContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuditContext implements AuditContextInterface
{
    /** @var array{actorId: ?string, actorEmail: ?string, ipAddress: ?string}|null */
    private ?array $current = null;

    // Id of the outbox_events row currently being consumed, set by OutboxConsumer right
    // before it dispatches — distinct from $current (which is the actor context captured
    // back when the event was originally published, and persisted alongside it). Read by
    // AuditLogRecorder to dedupe a redelivered event instead of recording it twice.
    private ?string $outboxEventId = null;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
    ) {
    }

    /** @return array{actorId: ?string, actorEmail: ?string, ipAddress: ?string} */
    public function capture(): array
    {
        // Mid-consumption of an outbox event (set() already restored the original
        // actor/IP from when that event was first published), a handler reacting to
        // it can itself cause a new domain event to be raised and flushed — e.g.
        // OnStockAdjustedHandler dispatching CreateNotificationCommand, whose
        // Notification::recordCreated() gets picked up by this same listener. That
        // derived event belongs to the same actor/request as the one being
        // processed, not to this worker process (which has no token or request of
        // its own) — so prefer the restored context over re-deriving from
        // TokenStorage/RequestStack, which would just return nulls here.
        if (null !== $this->current) {
            return $this->current;
        }

        $user = $this->tokenStorage->getToken()?->getUser();

        return [
            'actorId' => self::userId($user),
            'actorEmail' => $user instanceof UserInterface ? $user->getUserIdentifier() : null,
            'ipAddress' => $this->requestStack->getCurrentRequest()?->getClientIp(),
        ];
    }

    // getId() isn't part of UserInterface, so it's called dynamically (method_exists),
    // which means PHPStan can't know its return type either — narrow it explicitly
    // rather than casting the unchecked mixed straight to string.
    private static function userId(mixed $user): ?string
    {
        if (!$user instanceof UserInterface || !method_exists($user, 'getId')) {
            return null;
        }

        $id = $user->getId();

        return \is_scalar($id) ? (string) $id : null;
    }

    /** @param array<array-key, mixed> $context */
    public function set(array $context): void
    {
        $this->current = [
            'actorId' => self::stringOrNull($context['actorId'] ?? null),
            'actorEmail' => self::stringOrNull($context['actorEmail'] ?? null),
            'ipAddress' => self::stringOrNull($context['ipAddress'] ?? null),
        ];
    }

    /** @return array{actorId: ?string, actorEmail: ?string, ipAddress: ?string}|null */
    public function current(): ?array
    {
        return $this->current;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return \is_scalar($value) ? (string) $value : null;
    }

    public function setOutboxEventId(?string $outboxEventId): void
    {
        $this->outboxEventId = $outboxEventId;
    }

    public function outboxEventId(): ?string
    {
        return $this->outboxEventId;
    }

    public function clear(): void
    {
        $this->current = null;
        $this->outboxEventId = null;
    }
}
