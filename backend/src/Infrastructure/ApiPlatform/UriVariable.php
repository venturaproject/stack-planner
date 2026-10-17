<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform;

/**
 * ApiPlatform hands Provider/Processor::provide()/process() an untyped
 * `array $uriVariables` — each value is `mixed` as far as the type system
 * knows, even though it's always a route-matched string in practice. Narrow
 * it explicitly once here instead of at every call site.
 */
final class UriVariable
{
    /** @param array<array-key, mixed> $uriVariables */
    public static function int(array $uriVariables, string $key): int
    {
        $value = $uriVariables[$key] ?? null;

        return is_numeric($value) ? (int) $value : 0;
    }
}
