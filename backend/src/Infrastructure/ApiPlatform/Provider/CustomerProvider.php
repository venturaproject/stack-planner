<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Provider;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Customer\DTO\CustomerResponse;
use App\Application\Customer\Query\GetCustomerByIdQuery;
use App\Application\Customer\Query\ListCustomersQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Application\Shared\DTO\PagedResponse;
use App\Infrastructure\ApiPlatform\Pagination\ApiPaginator;
use App\Infrastructure\ApiPlatform\Resource\CustomerResource;
use DateTimeImmutable;
use Exception;

/** @implements ProviderInterface<CustomerResource|ApiPaginator<CustomerResource>> */
final class CustomerProvider implements ProviderInterface
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
            $hasAddress = self::filterString($filters, 'hasAddress');

            /** @var PagedResponse<CustomerResponse> $paged */
            $paged = $this->queryBus->ask(new ListCustomersQuery(
                page: $page,
                size: $size,
                search: self::filterString($filters, 'search'),
                hasAddress: null !== $hasAddress ? filter_var($hasAddress, \FILTER_VALIDATE_BOOLEAN) : null,
                createdFrom: self::parseDate(self::filterString($filters, 'createdFrom'), '00:00:00'),
                createdTo: self::parseDate(self::filterString($filters, 'createdTo'), '23:59:59'),
            ));

            return new ApiPaginator(
                items: array_map(static fn (CustomerResponse $r) => CustomerResource::fromResponse($r), $paged->data),
                currentPage: $page,
                itemsPerPage: $size,
                lastPage: $paged->totalPages,
                totalItems: $paged->totalElements,
            );
        }

        /** @var CustomerResponse $response */
        $response = $this->queryBus->ask(new GetCustomerByIdQuery(self::idFrom($uriVariables)));

        return CustomerResource::fromResponse($response);
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
