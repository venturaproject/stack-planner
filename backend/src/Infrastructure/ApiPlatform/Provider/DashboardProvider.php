<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Dashboard\DTO\DashboardResponse;
use App\Application\Dashboard\Query\GetDashboardQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Infrastructure\ApiPlatform\Resource\DashboardResource;

/** @implements ProviderInterface<DashboardResource> */
final class DashboardProvider implements ProviderInterface
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DashboardResource
    {
        /** @var DashboardResponse $response */
        $response = $this->queryBus->ask(new GetDashboardQuery());

        return DashboardResource::fromResponse($response);
    }
}
