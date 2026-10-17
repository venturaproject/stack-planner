<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Controller\Auth;

use App\Application\Auth\Command\LoginCommand;
use App\Infrastructure\Http\Controller\Auth\AuthController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

/**
 * Exercises the rate-limiting branches of AuthController directly: reaching the
 * real limit via HTTP would mean firing 1000+ requests per test, since the test
 * environment intentionally raises the limiter thresholds to avoid throttling
 * unrelated functional tests. A zero-headroom RateLimiterFactory reproduces the
 * "throttled" state deterministically instead. The controller is fetched from the
 * real container (rather than `new`'d) so `$this->json()` has a container to work
 * with, as it would in a real request.
 */
final class AuthControllerTest extends KernelTestCase
{
    /** A single-hit limiter, pre-consumed so the next attempt is always throttled. */
    private function exhaustedLimiter(string $key): RateLimiterFactory
    {
        $factory = new RateLimiterFactory(
            ['id' => 'test_limiter', 'policy' => 'fixed_window', 'limit' => 1, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );
        $factory->create($key)->consume(1);

        return $factory;
    }

    public function testLoginReturns429WhenRateLimited(): void
    {
        self::bootKernel();
        $controller = self::getContainer()->get(AuthController::class);
        $request = new Request(content: '{}', server: ['REMOTE_ADDR' => '203.0.113.1']);

        // #[MapRequestPayload] is resolved by the HTTP kernel's argument-resolution
        // pipeline, which a direct method call bypasses entirely — build the command
        // by hand instead; its content is never reached since the limiter check
        // (what this test exercises) runs before it.
        $response = $controller->login($request, $this->exhaustedLimiter('203.0.113.1'), new LoginCommand('', ''));

        self::assertSame(429, $response->getStatusCode());
    }

    public function testRefreshReturns429WhenRateLimited(): void
    {
        self::bootKernel();
        $controller = self::getContainer()->get(AuthController::class);
        $request = new Request(server: ['REMOTE_ADDR' => '203.0.113.2']);

        $response = $controller->refresh($request, $this->exhaustedLimiter('203.0.113.2'));

        self::assertSame(429, $response->getStatusCode());
    }

    public function testActivateReturns429WhenRateLimited(): void
    {
        self::bootKernel();
        $controller = self::getContainer()->get(AuthController::class);
        $request = new Request(content: '{}', server: ['REMOTE_ADDR' => '203.0.113.3']);

        $response = $controller->activate($request, $this->exhaustedLimiter('203.0.113.3'));

        self::assertSame(429, $response->getStatusCode());
    }

    public function testForgotPasswordReturns429WhenRateLimited(): void
    {
        self::bootKernel();
        $controller = self::getContainer()->get(AuthController::class);
        $request = new Request(content: '{}', server: ['REMOTE_ADDR' => '203.0.113.4']);

        $response = $controller->forgotPassword($request, $this->exhaustedLimiter('203.0.113.4'));

        self::assertSame(429, $response->getStatusCode());
    }

    public function testResetPasswordReturns429WhenRateLimited(): void
    {
        self::bootKernel();
        $controller = self::getContainer()->get(AuthController::class);
        $request = new Request(content: '{}', server: ['REMOTE_ADDR' => '203.0.113.5']);

        $response = $controller->resetPassword($request, $this->exhaustedLimiter('203.0.113.5'));

        self::assertSame(429, $response->getStatusCode());
    }
}
