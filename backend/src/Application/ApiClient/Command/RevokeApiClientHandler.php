<?php

declare(strict_types=1);

namespace App\Application\ApiClient\Command;

use App\Domain\ApiClient\Exception\ApiClientNotFoundException;
use App\Domain\ApiClient\Repository\ApiClientRepositoryInterface;
use App\Domain\ApiClient\Repository\ApiClientTokenRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class RevokeApiClientHandler
{
    public function __construct(
        private readonly ApiClientRepositoryInterface $clients,
        private readonly ApiClientTokenRepositoryInterface $tokens,
    ) {
    }

    public function handle(RevokeApiClientCommand $cmd): void
    {
        $client = $this->clients->findById($cmd->id) ?? throw new ApiClientNotFoundException();

        $this->tokens->deleteByApiClient($client);
        $client->deactivate();
        $this->clients->save($client);
    }
}
