<?php

declare(strict_types=1);

namespace App\Application\ApiClient\Command;

use App\Application\ApiClient\DTO\ApiClientResponse;
use App\Domain\ApiClient\Entity\ApiClient;
use App\Domain\ApiClient\Repository\ApiClientRepositoryInterface;

final class CreateApiClientHandler
{
    public function __construct(private readonly ApiClientRepositoryInterface $clients)
    {
    }

    public function handle(CreateApiClientCommand $cmd): CreateApiClientResult
    {
        $clientId = 'cli_'.bin2hex(random_bytes(8));
        $rawSecret = bin2hex(random_bytes(32));

        $client = new ApiClient(
            name: $cmd->name,
            clientId: $clientId,
            secretHash: hash('sha256', $rawSecret),
            scopes: $cmd->scopes,
        );

        return new CreateApiClientResult(
            client: ApiClientResponse::from($this->clients->save($client)),
            secret: $rawSecret,
        );
    }
}
