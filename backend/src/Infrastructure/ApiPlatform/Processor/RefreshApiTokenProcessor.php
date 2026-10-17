<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\ApiClient\Command\RefreshApiTokenCommand;
use App\Application\ApiClient\DTO\ApiTokenResponse;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\ApiPlatform\Resource\AuthResource;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/** @implements ProcessorInterface<AuthResource, AuthResource> */
final class RefreshApiTokenProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly RequestStack $requestStack,
        #[Autowire(service: 'limiter.api_client_refresh_limiter')]
        private readonly RateLimiterFactory $apiClientRefreshLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AuthResource
    {
        $ip = $this->requestStack->getCurrentRequest()?->getClientIp() ?? 'unknown';
        if (!$this->apiClientRefreshLimiter->create($ip)->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(message: 'Too many requests. Try again later.');
        }

        /** @var AuthResource $data */
        /** @var ApiTokenResponse $result */
        $result = $this->commandBus->dispatch(new RefreshApiTokenCommand((string) $data->refreshToken));

        return IssueApiTokenProcessor::fromResult($result);
    }
}
