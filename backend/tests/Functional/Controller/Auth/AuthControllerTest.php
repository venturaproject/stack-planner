<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Auth;

use App\Domain\Auth\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    private function createUser(string $email = 'test@test.com', string $password = 'password'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPasswordHash(password_hash($password, \PASSWORD_BCRYPT));
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function loginAs(string $email, string $password = 'password'): string
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password]) ?: ''
        );

        $data = json_decode((string) $this->client->getResponse()->getContent(), true);

        return (string) ($data['accessToken'] ?? '');
    }

    // ── Login ────────────────────────────────────────────────────────────────

    public function testLoginWithValidCredentials(): void
    {
        $this->createUser('login@test.com', 'password');
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'login@test.com', 'password' => 'password']) ?: ''
        );

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayNotHasKey('accessToken', $data);
        self::assertArrayHasKey('csrfToken', $data);
        self::assertArrayHasKey('user', $data);
        self::assertSame('login@test.com', $data['user']['email']);
    }

    public function testLoginSetsHttpOnlyCookies(): void
    {
        $this->createUser('cookies@test.com');
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'cookies@test.com', 'password' => 'password']) ?: ''
        );

        self::assertResponseIsSuccessful();
        $cookies = $this->client->getResponse()->headers->getCookies();
        $names = array_map(static fn ($c) => $c->getName(), $cookies);
        self::assertContains('access_token', $names);
        self::assertContains('refresh_token', $names);
        self::assertContains('csrf_token', $names);

        foreach ($cookies as $cookie) {
            // csrf_token is deliberately NOT HttpOnly — the frontend reads it via
            // document.cookie and echoes it back as X-CSRF-Token (double-submit
            // pattern, see CsrfProtectionListener). The JWT-bearing cookies must stay
            // HttpOnly so JS can never read them.
            $expectedHttpOnly = 'csrf_token' !== $cookie->getName();
            self::assertSame(
                $expectedHttpOnly,
                $cookie->isHttpOnly(),
                \sprintf('Cookie %s HttpOnly flag should be %s', $cookie->getName(), $expectedHttpOnly ? 'true' : 'false'),
            );
        }
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->createUser('fail@test.com', 'correct');
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'fail@test.com', 'password' => 'wrong']) ?: ''
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginWithNonExistentUser(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nobody@test.com', 'password' => 'password']) ?: ''
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginWithBlankEmailReturns422(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => '', 'password' => 'password']) ?: ''
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testLoginWithMissingEmailReturns422(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['password' => 'password']) ?: ''
        );

        self::assertResponseStatusCodeSame(422);
    }

    // ── Refresh ──────────────────────────────────────────────────────────────

    public function testRefreshTokenReturnsNewAccessToken(): void
    {
        $this->createUser('refresh@test.com');

        // Login: KernelBrowser automatically stores the refresh_token cookie
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'refresh@test.com', 'password' => 'password']) ?: ''
        );
        self::assertResponseIsSuccessful();

        $this->client->request('POST', '/api/auth/refresh');

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertArrayNotHasKey('accessToken', $data);
        self::assertArrayHasKey('csrfToken', $data);
    }

    public function testRefreshWithoutCookieReturns401(): void
    {
        $this->client->request('POST', '/api/auth/refresh');

        self::assertResponseStatusCodeSame(401);
    }

    // ── Logout ───────────────────────────────────────────────────────────────

    public function testLogoutReturns204AndClearsCookies(): void
    {
        $this->createUser('logout@test.com');
        $token = $this->loginAs('logout@test.com');

        $this->client->request(
            'POST',
            '/api/auth/logout',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token]
        );

        self::assertResponseStatusCodeSame(204);

        $cookies = $this->client->getResponse()->headers->getCookies();
        foreach ($cookies as $cookie) {
            if (\in_array($cookie->getName(), ['access_token', 'refresh_token'], true)) {
                self::assertTrue(
                    $cookie->isCleared() || '' === $cookie->getValue(),
                    "Cookie {$cookie->getName()} should be cleared after logout"
                );
            }
        }
    }

    public function testLogoutWithoutAuthReturns401(): void
    {
        $this->client->request('POST', '/api/auth/logout');

        self::assertResponseStatusCodeSame(401);
    }

    // ── Me ───────────────────────────────────────────────────────────────────

    public function testMeReturnsAuthenticatedUser(): void
    {
        $this->createUser('me@test.com');
        $token = $this->loginAs('me@test.com');

        $this->client->request(
            'GET',
            '/api/auth/me',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token]
        );

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('me@test.com', $data['user']['email']);
        self::assertNull($data['impersonatorEmail']);
        self::assertFalse($data['mustSetupTotp']);
    }

    public function testMeWithoutAuthReturns401(): void
    {
        $this->client->request('GET', '/api/auth/me');

        self::assertResponseStatusCodeSame(401);
    }

    // ── Protected endpoints ──────────────────────────────────────────────────

    public function testProtectedEndpointRequiresAuth(): void
    {
        $this->client->request('GET', '/api/notifications/');
        self::assertResponseStatusCodeSame(401);
    }

    public function testProtectedEndpointWithToken(): void
    {
        $this->createUser('auth@test.com');
        $token = $this->loginAs('auth@test.com');

        $this->client->request(
            'GET',
            '/api/notifications/',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token]
        );

        self::assertResponseIsSuccessful();
    }

    // ── Update profile ──────────────────────────────────────────────────────

    public function testUpdateMeChangesNameAndLanguage(): void
    {
        $this->createUser('profile@test.com');
        $token = $this->loginAs('profile@test.com');

        $this->client->request(
            'PATCH',
            '/api/auth/me',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['firstName' => 'New', 'lastName' => 'Name', 'preferredLanguage' => 'en']) ?: ''
        );

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('New', $data['firstName']);
        self::assertSame('en', $data['preferredLanguage']);
    }

    public function testUpdateMeReturns422OnValidationFailure(): void
    {
        $this->createUser('profile2@test.com');
        $token = $this->loginAs('profile2@test.com');

        $this->client->request(
            'PATCH',
            '/api/auth/me',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['firstName' => '', 'lastName' => 'Name']) ?: ''
        );

        self::assertResponseStatusCodeSame(422);
    }

    // ── Avatar upload ────────────────────────────────────────────────────────

    public function testUploadAvatarStoresFileAndReturnsUpdatedUser(): void
    {
        $this->createUser('avatar@test.com');
        $token = $this->loginAs('avatar@test.com');

        $path = sys_get_temp_dir().'/avatar-'.uniqid().'.png';
        file_put_contents($path, 'fake-image-bytes');
        $file = new UploadedFile($path, 'avatar.png', 'image/png', null, true);

        $this->client->request(
            'POST',
            '/api/auth/me/avatar',
            [],
            ['avatar' => $file],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token]
        );

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertStringContainsString('/media/avatars/', $data['avatarUrl']);

        @unlink($path);
    }

    public function testUploadAvatarReturns400WhenNoFileGiven(): void
    {
        $this->createUser('avatar2@test.com');
        $token = $this->loginAs('avatar2@test.com');

        $this->client->request(
            'POST',
            '/api/auth/me/avatar',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$token]
        );

        self::assertResponseStatusCodeSame(400);
    }
}
