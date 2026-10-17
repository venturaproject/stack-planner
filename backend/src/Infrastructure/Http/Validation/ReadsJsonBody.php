<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Validation;

use Symfony\Component\HttpFoundation\Request;

/**
 * For the handful of actions that hand-parse the JSON body instead of using
 * #[MapRequestPayload] (RoleController, PermissionController, ApiClientController,
 * and AuthController/TotpController's single-field bodies alongside a route/query
 * parameter) — a decoded body is `mixed` as far as the type system is concerned,
 * so every field read from it needs an explicit, narrow cast before it can reach a
 * typed Command constructor. These helpers do that narrowing once instead of at
 * every call site.
 */
trait ReadsJsonBody
{
    /** @return array<array-key, mixed> */
    private function decodeJsonBody(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        return \is_array($data) ? $data : [];
    }

    /** @param array<array-key, mixed> $data */
    private function stringField(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? null;

        return \is_scalar($value) ? (string) $value : $default;
    }

    /** @param array<array-key, mixed> $data */
    private function optionalStringField(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return \is_scalar($value) ? (string) $value : null;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return string[]
     */
    private function stringListField(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (!\is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $v): ?string => \is_scalar($v) ? (string) $v : null,
            $value,
        ), static fn (?string $v): bool => null !== $v));
    }
}
