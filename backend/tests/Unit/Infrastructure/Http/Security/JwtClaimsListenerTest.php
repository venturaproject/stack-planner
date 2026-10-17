<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Security;

use App\Infrastructure\Http\Security\JwtClaimsListener;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class JwtClaimsListenerTest extends TestCase
{
    public function testOnJwtCreatedAddsIssuerClaim(): void
    {
        $event = new JWTCreatedEvent(['sub' => 'user-1'], new InMemoryUser('user-1', null));

        (new JwtClaimsListener('https://app.example.com'))->onJwtCreated($event);

        self::assertSame('https://app.example.com', $event->getData()['iss']);
    }

    public function testOnJwtDecodedAcceptsMatchingIssuer(): void
    {
        $event = new JWTDecodedEvent(['iss' => 'https://app.example.com']);

        (new JwtClaimsListener('https://app.example.com'))->onJwtDecoded($event);

        self::assertTrue($event->isValid());
    }

    public function testOnJwtDecodedRejectsMismatchedIssuer(): void
    {
        $event = new JWTDecodedEvent(['iss' => 'https://attacker.example.com']);

        (new JwtClaimsListener('https://app.example.com'))->onJwtDecoded($event);

        self::assertFalse($event->isValid());
    }

    public function testOnJwtDecodedRejectsMissingIssuer(): void
    {
        $event = new JWTDecodedEvent([]);

        (new JwtClaimsListener('https://app.example.com'))->onJwtDecoded($event);

        self::assertFalse($event->isValid());
    }
}
