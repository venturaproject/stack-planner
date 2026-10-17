<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Double-submit CSRF check for the cookie-authenticated `api` firewall — see
 * AuthCookieFactory, which issues the csrf_token cookie on login/refresh (deliberately
 * NOT httpOnly, so the frontend can read it and echo it back as X-CSRF-Token).
 *
 * Requests carrying an explicit Authorization header are exempt: they aren't relying on
 * the browser's automatic cookie-sending, so a third-party page can't forge them
 * cross-site in the first place. That covers /api/v1's Bearer-token clients and any
 * header-based caller of the internal API (tests, scripts, curl).
 */
#[AsEventListener]
final class CsrfProtectionListener
{
    private const array MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const string EXEMPT_PATH_PATTERN = '#^/api/(v1|auth/(login|refresh|activate|forgot-password|reset-password)|health)#';

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!\in_array($request->getMethod(), self::MUTATING_METHODS, true)) {
            return;
        }

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        if (1 === preg_match(self::EXEMPT_PATH_PATTERN, $request->getPathInfo())) {
            return;
        }

        if ($request->headers->has('Authorization')) {
            return;
        }

        $cookieToken = $request->cookies->get('csrf_token');
        $headerToken = $request->headers->get('X-CSRF-Token');

        if (null === $cookieToken || null === $headerToken || !hash_equals($cookieToken, $headerToken)) {
            throw new AccessDeniedHttpException('Invalid or missing CSRF token.');
        }
    }
}
