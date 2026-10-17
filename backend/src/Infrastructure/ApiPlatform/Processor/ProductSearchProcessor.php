<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Product\DTO\ProductResponse;
use App\Application\Product\Query\ListProductsQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Application\Shared\DTO\PagedResponse;
use App\Infrastructure\ApiPlatform\Input\ProductSearchCriteria;
use App\Infrastructure\ApiPlatform\Pagination\ApiPaginator;
use App\Infrastructure\ApiPlatform\Resource\ProductResource;
use DateTimeImmutable;
use LogicException;

/** @implements ProcessorInterface<mixed, ApiPaginator<ProductResource>> */
final class ProductSearchProcessor implements ProcessorInterface
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    /** @return ApiPaginator<ProductResource> */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ApiPaginator
    {
        if (!$data instanceof ProductSearchCriteria) {
            throw new LogicException('Expected ProductSearchCriteria.');
        }

        $page = max(1, $data->page ?? 1);
        $itemsPerPage = min(100, max(1, $data->itemsPerPage ?? 20));

        /** @var PagedResponse<ProductResponse> $paged */
        $paged = $this->queryBus->ask(new ListProductsQuery(
            page: $page,
            size: $itemsPerPage,
            search: $data->search,
            stockLevel: $data->stockLevel,
            createdFrom: self::date($data->createdFrom, '00:00:00'),
            createdTo: self::date($data->createdTo, '23:59:59'),
            stockStatus: $data->stockStatus,
            priceMin: $data->priceMin,
            priceMax: $data->priceMax,
        ));

        return new ApiPaginator(
            items: array_map(static fn (ProductResponse $product) => ProductResource::fromResponse($product), $paged->data),
            currentPage: $page,
            itemsPerPage: $itemsPerPage,
            lastPage: $paged->totalPages,
            totalItems: $paged->totalElements,
        );
    }

    private static function date(?string $value, string $time): ?DateTimeImmutable
    {
        return null === $value ? null : new DateTimeImmutable($value.' '.$time);
    }
}
