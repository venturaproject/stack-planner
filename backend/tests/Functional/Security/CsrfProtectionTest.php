<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Support\DatabaseWebTestCase;

/**
 * Exercises CsrfProtectionListener against the real cookie-based login flow
 * (Symfony's test client persists Set-Cookie headers across requests on the
 * same client instance, same as a real browser) — as opposed to the rest of
 * the functional suite, which authenticates via the Authorization header and
 * is therefore CSRF-exempt by design (see the listener's docblock).
 */
final class CsrfProtectionTest extends DatabaseWebTestCase
{
    private function loginViaCookies(): void
    {
        $this->createAdmin('csrf-admin@test.com');
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['email' => 'csrf-admin@test.com', 'password' => 'password'])
        );
        self::assertResponseIsSuccessful();
    }

    public function testMutatingRequestWithoutCsrfHeaderIsRejected(): void
    {
        $this->loginViaCookies();

        $this->client->request(
            'POST',
            '/api/roles',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'no-csrf-header'])
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testMutatingRequestWithWrongCsrfHeaderIsRejected(): void
    {
        $this->loginViaCookies();

        $this->client->request(
            'POST',
            '/api/roles',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X-CSRF-Token' => 'not-the-real-token'],
            $this->jsonBody(['name' => 'wrong-csrf-header'])
        );

        self::assertResponseStatusCodeSame(403);
    }

    public function testMutatingRequestWithMatchingCsrfHeaderSucceeds(): void
    {
        $this->loginViaCookies();

        $csrfToken = $this->client->getCookieJar()->get('csrf_token')?->getValue();
        self::assertNotNull($csrfToken, 'login must set a csrf_token cookie');

        $this->client->request(
            'POST',
            '/api/roles',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X-CSRF-Token' => $csrfToken],
            $this->jsonBody(['name' => 'matching-csrf-header'])
        );

        self::assertResponseStatusCodeSame(201);
    }

    public function testGetRequestsDoNotRequireACsrfHeader(): void
    {
        $this->loginViaCookies();

        $this->client->request('GET', '/api/roles');

        self::assertResponseIsSuccessful();
    }
}
