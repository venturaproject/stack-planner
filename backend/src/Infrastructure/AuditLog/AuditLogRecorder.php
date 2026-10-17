<?php

declare(strict_types=1);

namespace App\Infrastructure\AuditLog;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
use App\Infrastructure\Outbox\AuditContext;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

final class AuditLogRecorder
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
        private readonly ?AuditContext $auditContext = null,
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function record(
        string $action,
        string $resourceType,
        ?string $resourceId = null,
        array $metadata = [],
        ?string $explicitActorId = null,
        ?string $explicitActorEmail = null,
    ): void {
        // Every call here is triggered by OutboxConsumer dispatching a domain event
        // (AuditLogSubscriber is its only caller), and the outbox only guarantees
        // at-least-once delivery: a crash between the dispatch succeeding and the
        // outbox row being marked processed replays the same event. Skip recording
        // again for an outbox row we've already written an entry for, so a redelivery
        // can't duplicate audit history (or corrupt the hash chain with a second link
        // for the same event).
        $outboxEventId = $this->auditContext?->outboxEventId();
        if (null !== $outboxEventId && $this->repository->existsByOutboxEventId($outboxEventId)) {
            return;
        }

        [$actorId, $actorEmail] = $this->resolveActor($explicitActorId, $explicitActorEmail);

        $context = $this->auditContext?->current();
        $ip = $context['ipAddress'] ?? $this->requestStack->getCurrentRequest()?->getClientIp();

        // getLastHash() and save() must be serialized against any other concurrent
        // record() call, not just this one: two outbox workers processing different
        // events at the same time could otherwise both read the same previousHash
        // before either has saved, breaking the chain's linearity. id/createdAt are
        // generated in here too, not before, so they reflect the moment this entry
        // actually gets its turn under the lock rather than whenever it happened to
        // reach this method — chain ordering itself doesn't depend on either of
        // them, though: see DoctrineAuditLogRepository::getLastHash() for why it
        // uses a real Postgres sequence instead.
        $this->repository->withHashChainLock(function () use (
            $actorId, $actorEmail, $action, $resourceType,
            $resourceId, $metadata, $ip, $outboxEventId,
        ): void {
            $id = Uuid::v7()->toRfc4122();
            $createdAt = new DateTimeImmutable();
            $previousHash = $this->repository->getLastHash();

            $hash = $this->computeHash(
                $id, $actorId, $actorEmail, $action, $resourceType,
                $resourceId, $metadata, $ip, $createdAt, $previousHash,
            );

            $this->repository->save(new AuditLog(
                id: $id,
                actorId: $actorId,
                actorEmail: $actorEmail,
                action: $action,
                resourceType: $resourceType,
                resourceId: $resourceId,
                metadata: $metadata,
                ipAddress: $ip,
                previousHash: $previousHash,
                hash: $hash,
                createdAt: $createdAt,
                outboxEventId: $outboxEventId,
            ));
        });
    }

    /** @return array{?string, ?string} */
    private function resolveActor(?string $explicitId, ?string $explicitEmail): array
    {
        if (null !== $explicitId) {
            return [$explicitId, $explicitEmail];
        }

        $context = $this->auditContext?->current();
        if (null !== $context) {
            return [$context['actorId'], $context['actorEmail']];
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof UserInterface) {
            return [null, null];
        }

        $rawId = method_exists($user, 'getId') ? $user->getId() : null;
        $id = \is_scalar($rawId) ? (string) $rawId : null;

        return [$id, $user->getUserIdentifier()];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function computeHash(
        string $id,
        ?string $actorId,
        ?string $actorEmail,
        string $action,
        string $resourceType,
        ?string $resourceId,
        array $metadata,
        ?string $ip,
        DateTimeImmutable $createdAt,
        string $previousHash,
    ): string {
        $content = implode("\0", [
            $id,
            $actorId ?? '',
            $actorEmail ?? '',
            $action,
            $resourceType,
            $resourceId ?? '',
            json_encode($metadata, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR),
            $ip ?? '',
            $createdAt->format(DateTimeInterface::ATOM),
            $previousHash,
        ]);

        return hash('sha256', $content);
    }
}
