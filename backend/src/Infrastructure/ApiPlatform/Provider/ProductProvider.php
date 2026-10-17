<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Provider;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Product\DTO\ProductResponse;
use App\Application\Product\Query\GetProductByIdQuery;
use App\Application\Product\Query\ListProductsQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Application\Shared\DTO\PagedResponse;
use App\Infrastructure\ApiPlatform\Pagination\ApiPaginator;
use App\Infrastructure\ApiPlatform\Resource\ProductResource;
use DateTimeImmutable;
use Exception;

/** @implements ProviderInterface<ProductResource|ApiPaginator<ProductResource>> */
final class ProductProvider implements ProviderInterface
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

            /** @var PagedResponse<ProductResponse> $paged */
            $paged = $this->queryBus->ask(new ListProductsQuery(
                page: $page,
                size: $size,
                search: self::filterString($filters, 'search'),
                stockLevel: self::filterString($filters, 'stockLevel'),
                createdFrom: self::parseDate(self::filterString($filters, 'createdFrom'), '00:00:00'),
                createdTo: self::parseDate(self::filterString($filters, 'createdTo'), '23:59:59'),
                stockStatus: self::filterString($filters, 'stockStatus'),
                priceMin: self::filterInt($filters, 'priceMin'),
                priceMax: self::filterInt($filters, 'priceMax'),
            ));

            return new ApiPaginator(
                items: array_map(static fn (ProductResponse $r) => ProductResource::fromResponse($r), $paged->data),
                currentPage: $page,
                itemsPerPage: $size,
                lastPage: $paged->totalPages,
                totalItems: $paged->totalElements,
            );
        }

        /** @var ProductResponse $response */
        $response = $this->queryBus->ask(new GetProductByIdQuery(self::idFrom($uriVariables)));

        return ProductResource::fromResponse($response);
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
