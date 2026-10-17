<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;
use DateTimeInterface;
use ReflectionClass;
use ReflectionProperty;
use RuntimeException;

final class OutboxEventCodec
{
    /** @return array<string, mixed> */
    public function encode(DomainEvent $event, ?string $aggregateName = null, string|int|null $aggregateId = null): array
    {
        $payload = ['occurredAt' => $event->occurredAt->format(DateTimeInterface::ATOM)];
        foreach (get_object_vars($event) as $name => $value) {
            $name = (string) $name;
            if ('occurredAt' !== $name) {
                $payload[$name] = $this->normalize($value);
            }
        }

        if (null !== $aggregateName && null !== $aggregateId) {
            $idProperty = lcfirst($aggregateName).'Id';
            if (\array_key_exists($idProperty, $payload) && null === $payload[$idProperty]) {
                $payload[$idProperty] = $aggregateId;
            }
        }

        return $payload;
    }

    /**
     * Bypasses the constructor entirely (newInstanceWithoutConstructor() + setting
     * each property directly via reflection) instead of calling it with the decoded
     * arguments. Going through the real constructor would call DomainEvent's, which
     * always sets occurredAt to the current time — losing the real occurrence time
     * this same class encoded into the payload in the first place, silently
     * replacing it with whenever the event happened to be redelivered/retried.
     *
     * @param array<array-key, mixed> $payload
     */
    public function decode(string $eventType, array $payload): DomainEvent
    {
        if (!is_a($eventType, DomainEvent::class, true)) {
            throw new RuntimeException(\sprintf('Outbox event "%s" is not a domain event.', $eventType));
        }

        $class = new ReflectionClass($eventType);
        $constructor = $class->getConstructor();
        if (null === $constructor) {
            throw new RuntimeException(\sprintf('Outbox event "%s" has no constructor.', $eventType));
        }

        /** @var DomainEvent $event */
        $event = $class->newInstanceWithoutConstructor();

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (!\array_key_exists($name, $payload)) {
                throw new RuntimeException(\sprintf('Outbox event "%s" is missing "%s".', $eventType, $name));
            }
            $class->getProperty($name)->setValue($event, $payload[$name]);
        }

        if (!\array_key_exists('occurredAt', $payload) || !\is_string($payload['occurredAt'])) {
            throw new RuntimeException(\sprintf('Outbox event "%s" is missing "occurredAt".', $eventType));
        }
        // occurredAt is declared on the abstract DomainEvent base class, not on $class
        // itself, so it needs its own ReflectionProperty rather than $class->getProperty().
        (new ReflectionProperty(DomainEvent::class, 'occurredAt'))->setValue(
            $event,
            new DateTimeImmutable($payload['occurredAt']),
        );

        return $event;
    }

    private function normalize(mixed $value): mixed
    {
        if (null === $value || \is_scalar($value)) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }
        if (\is_array($value)) {
            return array_map($this->normalize(...), $value);
        }

        throw new RuntimeException(\sprintf('Outbox payload cannot contain %s.', get_debug_type($value)));
    }
}
