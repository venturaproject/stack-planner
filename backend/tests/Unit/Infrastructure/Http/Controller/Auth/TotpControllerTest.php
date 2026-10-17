<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Controller\Auth;

use App\Domain\Auth\Entity\User;
use App\Infrastructure\Http\Controller\Auth\TotpController;
use App\Infrastructure\Security\Symfony\SecurityUser;
use App\Tests\Support\EntityIdSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

/**
 * Exercises the rate-limiting branches of TotpController directly, same approach as
 * AuthControllerTest — see its docblock for why a zero-headroom RateLimiterFactory is
 * used instead of firing enough real requests to hit the limit.
 */
final class TotpControllerTest extends KernelTestCase
{
    use EntityIdSetter;

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

    private function securityUser(string $id): SecurityUser
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        self::setId($user, $id);

        return new SecurityUser($user);
    }

    public function testConfirmReturns429WhenRateLimited(): void
    {
        self::bootKernel();
        $controller = self::getContainer()->get(TotpController::class);
        $user = $this->securityUser('user-1');

        $response = $controller->confirm($user, new Request(content: '{}'), $this->exhaustedLimiter('user-1'));

        self::assertSame(429, $response->getStatusCode());
    }

    public function testDisableReturns429WhenRateLimited(): void
    {
        self::bootKernel();
        $controller = self::getContainer()->get(TotpController::class);
        $user = $this->securityUser('user-2');

        $response = $controller->disable($user, new Request(content: '{}'), $this->exhaustedLimiter('user-2'));

        self::assertSame(429, $response->getStatusCode());
    }
}
