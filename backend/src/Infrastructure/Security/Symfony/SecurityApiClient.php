<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Symfony;

use App\Domain\ApiClient\Entity\ApiClient;
use LogicException;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityApiClient implements UserInterface
{
    /** @var non-empty-string */
    private readonly string $identifier;

    public function __construct(private readonly ApiClient $client)
    {
        $identifier = $client->getClientId();
        if ('' === $identifier) {
            throw new LogicException('An API client must have a client ID.');
        }

        $this->identifier = $identifier;
    }

    public function domainClient(): ApiClient
    {
        return $this->client;
    }

    public function getId(): ?string
    {
        return $this->client->getId();
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    /** @return string[] */
    public function getRoles(): array
    {
        $scopes = \in_array('*', $this->client->getScopes(), true) ? ApiClient::KNOWN_SCOPES : $this->client->getScopes();
        $roles = array_map(
            static fn (string $scope): string => 'ROLE_SCOPE_'.strtoupper(str_replace([':', '-'], '_', $scope)),
            $scopes,
        );

        return array_values(array_unique([...$roles, 'ROLE_API_CLIENT']));
    }

    public function eraseCredentials(): void
    {
    }
}
