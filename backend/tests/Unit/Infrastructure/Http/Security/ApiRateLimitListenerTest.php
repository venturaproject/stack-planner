<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Security;

use App\Infrastructure\Http\Security\ApiRateLimitListener;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class ApiRateLimitListenerTest extends TestCase
{
    /** A single-hit limiter, pre-consumed so the next attempt is always throttled. */
    private function exhaustedLimiter(string $key): RateLimiterFactory
    {
        $factory = new RateLimiterFactory(
            ['id' => 'test_api_limiter', 'policy' => 'fixed_window', 'limit' => 1, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );
        $factory->create($key)->consume(1);

        return $factory;
    }

    private function freshLimiter(): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => 'test_api_limiter', 'policy' => 'fixed_window', 'limit' => 100, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );
    }

    private function requestEvent(string $path, string $ip = '203.0.113.1', bool $isMain = true): RequestEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);
        $request = Request::create($path, server: ['REMOTE_ADDR' => $ip]);

        return new RequestEvent($kernel, $request, $isMain ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST);
    }

    public function testThrowsWhenAnonymousCallerExhaustsTheLimitByIp(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $listener = new ApiRateLimitListener($this->exhaustedLimiter('ip:203.0.113.1'), $security);

        $this->expectException(TooManyRequestsHttpException::class);

        $listener($this->requestEvent('/api/admin/users'));
    }

    public function testThrowsWhenAuthenticatedUserExhaustsTheLimitByUserId(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new InMemoryUser('user-1', null));

        $listener = new ApiRateLimitListener($this->exhaustedLimiter('user:user-1'), $security);

        $this->expectException(TooManyRequestsHttpException::class);

        $listener($this->requestEvent('/api/admin/users'));
    }

    public function testAllowsRequestsWithinTheLimit(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $listener = new ApiRateLimitListener($this->freshLimiter(), $security);

        $listener($this->requestEvent('/api/admin/users'));

        $this->addToAssertionCount(1);
    }

    public function testSkipsHealthCheckPath(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $listener = new ApiRateLimitListener($this->exhaustedLimiter('ip:203.0.113.1'), $security);

        $listener($this->requestEvent('/api/health'));

        $this->addToAssertionCount(1);
    }

    public function testSkipsNonApiPaths(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $listener = new ApiRateLimitListener($this->exhaustedLimiter('ip:203.0.113.1'), $security);

        $listener($this->requestEvent('/some-other-route'));

        $this->addToAssertionCount(1);
    }

    public function testSkipsSubRequests(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $listener = new ApiRateLimitListener($this->exhaustedLimiter('ip:203.0.113.1'), $security);

        $listener($this->requestEvent('/api/admin/users', isMain: false));

        $this->addToAssertionCount(1);
    }
}
