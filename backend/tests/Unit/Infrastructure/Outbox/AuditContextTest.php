<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Outbox;

use App\Infrastructure\Outbox\AuditContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AuditContextTest extends TestCase
{
    public function testCaptureDerivesFromTokenStorageWhenNoContextIsRestored(): void
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);
        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        $context = new AuditContext($tokenStorage, $requestStack);

        self::assertSame(
            ['actorId' => null, 'actorEmail' => null, 'ipAddress' => null],
            $context->capture(),
        );
    }

    public function testCapturePrefersARestoredContextOverLiveTokenStorage(): void
    {
        // Regression test: mid-consumption of an outbox event, a handler reacting
        // to it can cause a second domain event to be raised and flushed (e.g. a
        // notification created as a side effect) — DoctrineOutboxListener calls
        // capture() again for that second event. It must inherit the actor/IP
        // restored via set() (the original publisher's), not re-derive from this
        // worker process's own — empty — security context.
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);
        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(Request::create('/'));

        $context = new AuditContext($tokenStorage, $requestStack);
        $context->set(['actorId' => 'user-1', 'actorEmail' => 'me@example.com', 'ipAddress' => '203.0.113.5']);

        self::assertSame(
            ['actorId' => 'user-1', 'actorEmail' => 'me@example.com', 'ipAddress' => '203.0.113.5'],
            $context->capture(),
        );
    }

    public function testClearRestoresLiveTokenStorageDerivation(): void
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);
        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        $context = new AuditContext($tokenStorage, $requestStack);
        $context->set(['actorId' => 'user-1', 'actorEmail' => 'me@example.com', 'ipAddress' => '203.0.113.5']);
        $context->clear();

        self::assertSame(
            ['actorId' => null, 'actorEmail' => null, 'ipAddress' => null],
            $context->capture(),
        );
    }
}
