<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\RefreshTokenCommand;
use App\Application\Auth\Command\RefreshTokenHandler;
use App\Application\Auth\Exception\InvalidCredentialsException;
use App\Application\Auth\Security\AccessTokenIssuerInterface;
use App\Domain\Auth\Entity\RefreshToken;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RefreshTokenHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function user(): User
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        self::setId($user, 'user-1');

        return $user;
    }

    public function testIssuesNewTokenPairAndRevokesOldOne(): void
    {
        $user = $this->user();
        $token = new RefreshToken(hash('sha256', 'old-value'), $user, new DateTimeImmutable('+1 hour'));

        $refreshTokens = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokens->method('findByToken')->willReturn($token);
        $refreshTokens->expects(self::once())->method('save')
            ->with(self::isInstanceOf(RefreshToken::class))
            ->willReturnArgument(0);

        $jwtManager = $this->createStub(AccessTokenIssuerInterface::class);
        $jwtManager->method('issue')->willReturn('new-jwt');

        $handler = new RefreshTokenHandler($refreshTokens, $jwtManager);

        $result = $handler->handle(new RefreshTokenCommand('old-value'));

        self::assertSame('new-jwt', $result->accessToken);
        self::assertNotSame('old-value', $result->refreshToken);
        self::assertTrue($token->isRevoked());
    }

    /**
     * Regression test for a real production bug: refreshing used to compute a
     * brand-new expiry from "now" on every call. Since the frontend refreshes
     * silently and automatically (whenever the access token expires, ~every 15
     * min of activity), that made the session effectively immortal — it never
     * died as long as it was used at least once within every refresh-TTL
     * window, no matter how long ago the original login was. The fix carries
     * the ORIGINAL token's expiry forward unchanged, making it an absolute
     * cap from login instead of a sliding one.
     */
    public function testCarriesTheOriginalExpiryForwardInsteadOfExtendingIt(): void
    {
        $user = $this->user();
        $originalExpiresAt = new DateTimeImmutable('+2 days');
        $token = new RefreshToken(hash('sha256', 'old-value'), $user, $originalExpiresAt);

        $refreshTokens = $this->createMock(RefreshTokenRepositoryInterface::class);
        $refreshTokens->method('findByToken')->willReturn($token);
        $refreshTokens->expects(self::once())->method('save')
            ->with(self::callback(
                static fn (RefreshToken $saved): bool => $saved->getExpiresAt() == $originalExpiresAt
            ))
            ->willReturnArgument(0);

        $jwtManager = $this->createStub(AccessTokenIssuerInterface::class);
        $jwtManager->method('issue')->willReturn('new-jwt');

        $handler = new RefreshTokenHandler($refreshTokens, $jwtManager);

        $result = $handler->handle(new RefreshTokenCommand('old-value'));

        self::assertEquals($originalExpiresAt, $result->refreshExpires);
    }

    public function testThrowsWhenTokenNotFound(): void
    {
        $refreshTokens = $this->createStub(RefreshTokenRepositoryInterface::class);
        $refreshTokens->method('findByToken')->willReturn(null);

        $handler = new RefreshTokenHandler($refreshTokens, $this->createStub(AccessTokenIssuerInterface::class));

        $this->expectException(InvalidCredentialsException::class);

        $handler->handle(new RefreshTokenCommand('unknown'));
    }

    public function testThrowsWhenTokenIsExpired(): void
    {
        $user = $this->user();
        $token = new RefreshToken(hash('sha256', 'expired-value'), $user, new DateTimeImmutable('-1 hour'));

        $refreshTokens = $this->createStub(RefreshTokenRepositoryInterface::class);
        $refreshTokens->method('findByToken')->willReturn($token);

        $handler = new RefreshTokenHandler($refreshTokens, $this->createStub(AccessTokenIssuerInterface::class));

        $this->expectException(InvalidCredentialsException::class);

        $handler->handle(new RefreshTokenCommand('expired-value'));
    }

    public function testThrowsWhenTokenIsRevoked(): void
    {
        $user = $this->user();
        $token = new RefreshToken(hash('sha256', 'revoked-value'), $user, new DateTimeImmutable('+1 hour'));
        $token->revoke();

        $refreshTokens = $this->createStub(RefreshTokenRepositoryInterface::class);
        $refreshTokens->method('findByToken')->willReturn($token);

        $handler = new RefreshTokenHandler($refreshTokens, $this->createStub(AccessTokenIssuerInterface::class));

        $this->expectException(InvalidCredentialsException::class);

        $handler->handle(new RefreshTokenCommand('revoked-value'));
    }
}
