<?php

declare(strict_types=1);

namespace App\Application\ApiClient\Query;

use App\Application\ApiClient\DTO\ApiClientResponse;

final class ListApiClientsHandler
{
    public function __construct(private readonly ApiClientReadModelInterface $clients)
    {
    }

    /** @return ApiClientResponse[] */
    public function handle(ListApiClientsQuery $query): array
    {
        return array_map(ApiClientResponse::from(...), $this->clients->findAllClients());
    }
}
