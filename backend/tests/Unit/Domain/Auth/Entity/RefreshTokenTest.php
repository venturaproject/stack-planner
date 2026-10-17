<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Auth\Entity;

use App\Domain\Auth\Entity\RefreshToken;
use App\Domain\Auth\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RefreshTokenTest extends TestCase
{
    private function user(): User
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');

        return $user;
    }

    public function testExposesTokenUserAndTimestamps(): void
    {
        $user = $this->user();
        $expiresAt = new DateTimeImmutable('+1 hour');

        $token = new RefreshToken('hashed-value', $user, $expiresAt);

        self::assertNull($token->getId());
        self::assertSame('hashed-value', $token->getToken());
        self::assertSame($user, $token->getUser());
        self::assertSame($expiresAt, $token->getExpiresAt());
        self::assertInstanceOf(DateTimeImmutable::class, $token->getCreatedAt());
    }

    public function testIsValidWhenNeitherExpiredNorRevoked(): void
    {
        $token = new RefreshToken('value', $this->user(), new DateTimeImmutable('+1 hour'));

        self::assertTrue($token->isValid());
        self::assertFalse($token->isExpired());
        self::assertFalse($token->isRevoked());
    }

    public function testIsInvalidOnceRevoked(): void
    {
        $token = new RefreshToken('value', $this->user(), new DateTimeImmutable('+1 hour'));

        $token->revoke();

        self::assertTrue($token->isRevoked());
        self::assertFalse($token->isValid());
    }

    public function testIsInvalidOnceExpired(): void
    {
        $token = new RefreshToken('value', $this->user(), new DateTimeImmutable('-1 hour'));

        self::assertTrue($token->isExpired());
        self::assertFalse($token->isValid());
    }
}
