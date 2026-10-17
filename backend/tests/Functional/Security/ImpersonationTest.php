<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Domain\Auth\Entity\User;
use App\Tests\Support\DatabaseWebTestCase;

/**
 * Cookie-based end to end: log in as admin over cookies (not Authorization header,
 * see CsrfProtectionTest's docblock for why that distinction matters), impersonate a
 * target user, confirm /api/auth/me reflects it, then stop impersonating and confirm
 * control reverts to the admin — all through the real cookie jar, like a browser.
 */
final class ImpersonationTest extends DatabaseWebTestCase
{
    private function csrfHeader(): array
    {
        $token = $this->client->getCookieJar()->get('csrf_token')?->getValue();
        self::assertNotNull($token);

        return ['HTTP_X-CSRF-Token' => $token];
    }

    private function loginAsAdminViaCookies(): void
    {
        $this->createAdmin('impersonate-admin@test.com');
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['email' => 'impersonate-admin@test.com', 'password' => 'password'])
        );
        self::assertResponseIsSuccessful();
    }

    public function testAdminCanImpersonateAnActiveUser(): void
    {
        $this->loginAsAdminViaCookies();
        $target = $this->createUser('impersonate-target@test.com');

        $this->client->request(
            'POST',
            "/api/users/{$target->getId()}/impersonate",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'] + $this->csrfHeader(),
        );

        self::assertResponseStatusCodeSame(200);
        $data = $this->decodeResponse();
        self::assertSame('impersonate-target@test.com', $data['user']['email']);

        $cookies = $this->client->getResponse()->headers->getCookies();
        $names = array_map(static fn ($c) => $c->getName(), $cookies);
        self::assertContains('access_token', $names);
        // No refresh_token in the response: an impersonation session can't silently
        // refresh itself. It expires or goes through stop-impersonating.
        $refreshCookie = current(array_filter($cookies, static fn ($c) => 'refresh_token' === $c->getName()));
        self::assertNotFalse($refreshCookie);
        self::assertTrue($refreshCookie->getExpiresTime() < time(), 'refresh_token cookie should be cleared, not renewed');
    }

    public function testNonAdminCannotImpersonate(): void
    {
        $this->createUser('plain@test.com');
        $token = $this->loginAs('plain@test.com');
        $target = $this->createUser('impersonate-target2@test.com');

        $this->client->request(
            'POST',
            "/api/users/{$target->getId()}/impersonate",
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCannotImpersonateThemselves(): void
    {
        $this->createAdmin('self-impersonate@test.com');
        $token = $this->loginAs('self-impersonate@test.com');
        $admin = $this->em->getRepository(User::class)
            ->findOneBy(['email' => 'self-impersonate@test.com']);

        $this->client->request(
            'POST',
            "/api/users/{$admin->getId()}/impersonate",
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
        );

        self::assertResponseStatusCodeSame(409);
    }

    public function testMeReflectsTheImpersonatorWhileImpersonating(): void
    {
        $this->loginAsAdminViaCookies();
        $target = $this->createUser('impersonate-target3@test.com');

        $this->client->request(
            'POST',
            "/api/users/{$target->getId()}/impersonate",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'] + $this->csrfHeader(),
        );
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/auth/me');

        self::assertResponseIsSuccessful();
        $data = $this->decodeResponse();
        self::assertSame('impersonate-target3@test.com', $data['user']['email']);
        self::assertSame('impersonate-admin@test.com', $data['impersonatorEmail']);
    }

    public function testStopImpersonatingRevertsToTheAdmin(): void
    {
        $this->loginAsAdminViaCookies();
        $target = $this->createUser('impersonate-target4@test.com');

        $this->client->request(
            'POST',
            "/api/users/{$target->getId()}/impersonate",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'] + $this->csrfHeader(),
        );
        self::assertResponseIsSuccessful();

        $this->client->request(
            'POST',
            '/api/auth/stop-impersonating',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'] + $this->csrfHeader(),
        );

        self::assertResponseStatusCodeSame(200);
        $data = $this->decodeResponse();
        self::assertSame('impersonate-admin@test.com', $data['user']['email']);

        $this->client->request('GET', '/api/auth/me');
        $me = $this->decodeResponse();
        self::assertSame('impersonate-admin@test.com', $me['user']['email']);
        self::assertNull($me['impersonatorEmail']);
    }

    public function testStopImpersonatingWhenNotImpersonatingReturns400(): void
    {
        $this->loginAsAdminViaCookies();

        $this->client->request(
            'POST',
            '/api/auth/stop-impersonating',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'] + $this->csrfHeader(),
        );

        self::assertResponseStatusCodeSame(400);
    }
}
