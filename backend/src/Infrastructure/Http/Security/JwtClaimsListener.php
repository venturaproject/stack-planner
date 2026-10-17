<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class JwtClaimsListener
{
    public function __construct(private readonly string $appUrl)
    {
    }

    #[AsEventListener(event: Events::JWT_CREATED)]
    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        $payload['iss'] = $this->appUrl;
        $event->setData($payload);
    }

    #[AsEventListener(event: Events::JWT_DECODED)]
    public function onJwtDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        if (!isset($payload['iss']) || $payload['iss'] !== $this->appUrl) {
            $event->markAsInvalid();
        }
    }
}
