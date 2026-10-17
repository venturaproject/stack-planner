<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\ApiClient\Command\IssueApiTokenCommand;
use App\Application\ApiClient\DTO\ApiTokenResponse;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\ApiPlatform\Resource\AuthResource;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/** @implements ProcessorInterface<AuthResource, AuthResource> */
final class IssueApiTokenProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly RequestStack $requestStack,
        #[Autowire(service: 'limiter.api_client_token_limiter')]
        private readonly RateLimiterFactory $apiClientTokenLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthResource
    {
        $ip = $this->requestStack->getCurrentRequest()?->getClientIp() ?? 'unknown';
        if (!$this->apiClientTokenLimiter->create($ip)->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Too many token requests. Try again later.');
        }

        /** @var AuthResource $data */
        /** @var ApiTokenResponse $result */
        $result = $this->commandBus->dispatch(new IssueApiTokenCommand(
            clientId: (string) $data->clientId,
            clientSecret: (string) $data->clientSecret,
        ));

        return self::fromResult($result);
    }

    public static function fromResult(ApiTokenResponse $result): AuthResource
    {
        $resource = new AuthResource();
        $resource->accessToken = $result->accessToken;
        $resource->refreshToken = $result->refreshToken;
        $resource->tokenType = $result->tokenType;
        $resource->expiresIn = $result->expiresIn;
        $resource->scopes = $result->scopes;

        return $resource;
    }
}
