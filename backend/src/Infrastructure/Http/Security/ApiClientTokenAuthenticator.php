<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Security;

use App\Domain\ApiClient\Repository\ApiClientRepositoryInterface;
use App\Domain\ApiClient\Repository\ApiClientTokenRepositoryInterface;
use App\Infrastructure\Security\Symfony\SecurityApiClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiClientTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly ApiClientTokenRepositoryInterface $tokens,
        private readonly ApiClientRepositoryInterface $clients,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with((string) $request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $rawToken = substr((string) $request->headers->get('Authorization'), 7);
        $hash = hash('sha256', $rawToken);

        $token = $this->tokens->findByAccessTokenHash($hash);
        if (null === $token || $token->isAccessExpired()) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired access token');
        }

        $client = $token->getApiClient();

        return new SelfValidatingPassport(new UserBadge($client->getClientId(), function () use ($client): SecurityApiClient {
            if (!$client->isActive()) {
                throw new CustomUserMessageAuthenticationException('Unknown or inactive API client');
            }

            $this->clients->updateLastUsed($client->getClientId());

            return new SecurityApiClient($client);
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
