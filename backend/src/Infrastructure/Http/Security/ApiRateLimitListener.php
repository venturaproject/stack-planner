<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Baseline throttle for the whole API, on top of (not instead of) the tighter
 * per-endpoint limiters already on login/refresh/api-client-token: those exist because
 * an *unauthenticated* attacker can hammer them for free, so they need to be tight and
 * IP-keyed. Everything else — the authenticated admin CRUD surface — had no cap at all
 * before this: a compromised token, a buggy client stuck polling, or plain abuse could
 * hit any endpoint without limit. This is deliberately generous (300 req/min) since its
 * job is catching a runaway client, not shaping normal traffic.
 *
 * Keyed per authenticated user when there is one, falling back to IP for anonymous
 * requests (health checks aside) — matches the natural boundary better than a pure IP
 * key would, e.g. several staff behind the same office NAT don't share one bucket.
 */
#[AsEventListener]
final class ApiRateLimitListener
{
    private const string EXEMPT_PATH_PATTERN = '#^/api/health#';

    public function __construct(
        #[Autowire(service: 'limiter.api_limiter')]
        private readonly RateLimiterFactory $apiLimiter,
        private readonly Security $security,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/api') || 1 === preg_match(self::EXEMPT_PATH_PATTERN, $path)) {
            return;
        }

        $user = $this->security->getUser();
        $key = $user instanceof UserInterface
            ? 'user:'.$user->getUserIdentifier()
            : 'ip:'.($request->getClientIp() ?? 'unknown');

        if (!$this->apiLimiter->create($key)->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(null, 'Too many requests. Please slow down.');
        }
    }
}
