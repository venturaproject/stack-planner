<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Provider;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Order\DTO\OrderResponse;
use App\Application\Order\Query\GetOrderByIdQuery;
use App\Application\Order\Query\ListOrdersQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Application\Shared\DTO\PagedResponse;
use App\Infrastructure\ApiPlatform\Pagination\ApiPaginator;
use App\Infrastructure\ApiPlatform\Resource\OrderResource;
use DateTimeImmutable;
use Exception;

/** @implements ProviderInterface<OrderResource|ApiPaginator<OrderResource>> */
final class OrderProvider implements ProviderInterface
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array
    {
        if ($operation instanceof GetCollection) {
            $filters = $context['filters'] ?? [];
            $filters = \is_array($filters) ? $filters : [];

            $page = max(1, self::filterInt($filters, 'page') ?? 1);
            $size = max(1, min(100, self::filterInt($filters, 'itemsPerPage') ?? 20));

            /** @var PagedResponse<OrderResponse> $paged */
            $paged = $this->queryBus->ask(new ListOrdersQuery(
                page: $page,
                size: $size,
                customerId: self::filterInt($filters, 'customerId'),
                status: self::filterString($filters, 'status'),
                createdFrom: self::parseDate(self::filterString($filters, 'createdFrom'), '00:00:00'),
                createdTo: self::parseDate(self::filterString($filters, 'createdTo'), '23:59:59'),
            ));

            return new ApiPaginator(
                items: array_map(static fn (OrderResponse $r) => OrderResource::fromResponse($r), $paged->data),
                currentPage: $page,
                itemsPerPage: $size,
                lastPage: $paged->totalPages,
                totalItems: $paged->totalElements,
            );
        }

        /** @var OrderResponse $response */
        $response = $this->queryBus->ask(new GetOrderByIdQuery(self::idFrom($uriVariables)));

        return OrderResource::fromResponse($response);
    }

    /** @param array<array-key, mixed> $filters */
    private static function filterString(array $filters, string $key): ?string
    {
        $value = $filters[$key] ?? null;

        return \is_string($value) && '' !== $value ? $value : null;
    }

    /** @param array<array-key, mixed> $filters */
    private static function filterInt(array $filters, string $key): ?int
    {
        $value = self::filterString($filters, $key);

        return null !== $value ? (int) $value : null;
    }

    /** @param array<array-key, mixed> $uriVariables */
    private static function idFrom(array $uriVariables): int
    {
        $id = $uriVariables['id'] ?? null;

        return is_numeric($id) ? (int) $id : 0;
    }

    private static function parseDate(?string $value, string $time): ?DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value.' '.$time);
        } catch (Exception) {
            return null;
        }
    }
}
