<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Auth;

use App\Application\Auth\Command\LoginResult;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * All three auth cookies (access_token, refresh_token, csrf_token) are set with
 * SameSite=Strict. That's safe here only because the frontend and this API are
 * always served from the same origin (nginx proxies both — see
 * infrastructure/nginx/nginx.dev.conf / nginx.prod.conf). SameSite=Strict cookies
 * are not sent on cross-site requests at all, including top-level cross-site
 * navigation (unlike Lax). If this backend is ever deployed with the SPA and the
 * API on genuinely different registrable domains (not just a subdomain split
 * behind the same reverse proxy), every authenticated request will silently drop
 * these cookies and login will appear to succeed but never actually persist.
 * Downgrade to SameSite=Lax for access_token/refresh_token if that split is ever
 * needed (csrf_token can stay Strict either way, since it's read via
 * document.cookie, not sent automatically by the browser as a cross-site cookie).
 */
final class AuthCookieFactory
{
    public function __construct(private readonly bool $cookieSecure, private readonly string $sameSite)
    {
    }

    /** @return ''|'lax'|'none'|'strict'|null */
    private function validatedSameSite(): ?string
    {
        return match ($this->sameSite) {
            '', 'lax', 'none', 'strict' => $this->sameSite,
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
                ->withSameSite('strict')
        );
        // Double-submit CSRF cookie: deliberately NOT httpOnly — the frontend reads
        // it via document.cookie and echoes it back as the X-CSRF-Token header (see
        // CsrfProtectionListener) on every mutating request. sameSite=strict already
        // stops the cookie itself from riding along on cross-site requests, but this
        // is defense-in-depth against any future sameSite/browser regressions.
        $this->setCsrfCookie($response, $csrfToken ?? $this->createCsrfToken());
    }
}
