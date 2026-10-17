<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * When SecuritySettings.require2fa is on and a user logs in without TOTP configured
 * yet, LoginHandler issues a JWT carrying `must_setup_totp: true` instead of a normal
 * token (see LoginHandler). This listener confines that session to the TOTP setup
 * endpoints only, until they actually complete setup — otherwise the policy would be
 * purely cosmetic (a user could just... not set it up and keep using a full session).
 */
#[AsEventListener]
final class Require2faEnforcementListener
{
    private const string EXEMPT_PATH_PATTERN = '#^/api/(auth/(totp/|logout$|me$)|health)#';

    public function __construct(
        private readonly Security $security,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (!str_starts_with($path, '/api') || 1 === preg_match(self::EXEMPT_PATH_PATTERN, $path)) {
            return;
        }

        $token = $this->security->getToken();
        if (null === $token) {
            return;
        }

        $payload = $this->jwtManager->decode($token);
        if (\is_array($payload) && true === ($payload['must_setup_totp'] ?? false)) {
            throw new AccessDeniedHttpException('Two-factor authentication setup is required before continuing.');
        }
    }
}
