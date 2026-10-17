<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Auth;

use App\Application\Auth\Command\LoginResult;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * All three auth cookies (access_token, refresh_token, csrf_token) use the configured
 * SameSite policy, which defaults to Strict. That's safe here because the frontend and
 * this API are served from the same origin (nginx proxies both — see
 * infrastructure/nginx/nginx.dev.conf / nginx.prod.conf). SameSite=Strict cookies
 * are not sent on cross-site requests at all, including top-level cross-site
 * navigation (unlike Lax). If the SPA and API are ever deployed on different
 * registrable domains, SameSite=Lax can be configured after reviewing the CSRF
 * implications. SameSite=None is deliberately rejected: the refresh route relies
 * on cross-site cookie suppression as a defense-in-depth control.
 */
final class AuthCookieFactory
{
    public function __construct(private readonly bool $cookieSecure, private readonly string $sameSite)
    {
    }

    private function validatedSameSite(): string
    {
        return match (strtolower(trim($this->sameSite))) {
            'lax' => 'lax',
            'strict' => 'strict',
            default => 'strict',
        };
    }

    public function setCookies(Response $response, LoginResult $result, string $csrfToken): void
    {
        $this->setSessionCookies($response, $result->accessToken, $csrfToken);
        $response->headers->setCookie(
            Cookie::create('refresh_token')
                ->withValue($result->refreshToken)
                ->withExpires($result->refreshExpires)
                ->withPath('/')
                ->withSecure($this->cookieSecure)
                ->withHttpOnly(true)
                ->withSameSite($this->validatedSameSite())
        );
    }

    /**
     * Sets an access_token (+ a fresh csrf_token) but deliberately no refresh_token,
     * and clears whatever refresh_token the admin's own session already had — an
     * impersonation session can't silently refresh itself back into existence once
     * the access token expires; it has to go through /api/auth/stop-impersonating
     * (or log in again) explicitly. See ImpersonateUserHandler.
     */
    public function setImpersonationCookies(Response $response, string $accessToken): void
    {
        $this->setSessionCookies($response, $accessToken);
        $response->headers->clearCookie('refresh_token', '/');
    }

    /**
     * Sets a fresh access_token (+ csrf_token) without touching refresh_token — used
     * when a claim baked into the current JWT needs to be dropped mid-session (e.g.
     * the `must_setup_totp` claim once TOTP setup is confirmed) without forcing a
     * full re-login.
     */
    public function refreshAccessTokenCookie(Response $response, string $accessToken): void
    {
        $this->setSessionCookies($response, $accessToken);
    }

    public function clearCookies(Response $response): void
    {
        $response->headers->clearCookie('access_token', '/');
        $response->headers->clearCookie('refresh_token', '/');
        $response->headers->clearCookie('csrf_token', '/');
    }

    public function createCsrfToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function setCsrfCookie(Response $response, string $csrfToken): void
    {
        $response->headers->setCookie(
            Cookie::create('csrf_token')
                ->withValue($csrfToken)
                ->withPath('/')
                ->withSecure($this->cookieSecure)
                ->withHttpOnly(false)
                ->withSameSite($this->validatedSameSite())
        );
    }

    private function setSessionCookies(Response $response, string $accessToken, ?string $csrfToken = null): void
    {
        $response->headers->setCookie(
            Cookie::create('access_token')
                ->withValue($accessToken)
                ->withPath('/')
                ->withSecure($this->cookieSecure)
                ->withHttpOnly(true)
                ->withSameSite($this->validatedSameSite())
        );
        // Double-submit CSRF cookie: deliberately NOT httpOnly — the frontend reads
        // it via document.cookie and echoes it back as the X-CSRF-Token header (see
        // CsrfProtectionListener) on every mutating request. sameSite=strict already
        // stops the cookie itself from riding along on cross-site requests, but this
        // is defense-in-depth against any future sameSite/browser regressions.
        $this->setCsrfCookie($response, $csrfToken ?? $this->createCsrfToken());
    }
}
