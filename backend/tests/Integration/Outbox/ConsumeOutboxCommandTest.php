<?php

declare(strict_types=1);

namespace App\Tests\Integration\Outbox;

use App\Domain\Auth\Event\UserLoggedIn;
use App\Infrastructure\Outbox\AuditContext;
use App\Infrastructure\Outbox\ConsumeOutboxCommand;
use App\Infrastructure\Outbox\OutboxConsumer;
use App\Infrastructure\Outbox\OutboxEventCodec;
use App\Tests\Support\DatabaseKernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ConsumeOutboxCommandTest extends DatabaseKernelTestCase
{
    public function testExitsNonZeroInsteadOfLoopingForeverWhenTheEntityManagerCloses(): void
    {
        $this->em->getConnection()->insert('outbox_events', [
            'id' => '77777777-7777-7777-7777-777777777777',
            'event_type' => UserLoggedIn::class,
            'payload' => json_encode(['userId' => 'user-1', 'email' => 'me@example.com', 'occurredAt' => '2026-01-01T00:00:00+00:00'], \JSON_THROW_ON_ERROR),
            'context' => '{}',
            'occurred_at' => '2026-01-01 00:00:00+00',
            'available_at' => '2026-01-01 00:00:00+00',
        ]);

        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function () {
            $this->em->close();

            return null;
        });

        $consumer = new OutboxConsumer(
            $this->em->getConnection(),
            new OutboxEventCodec(),
            new AuditContext($this->createStub(TokenStorageInterface::class), $this->createStub(RequestStack::class)),
            $dispatcher,
            $this->em,
        );

        $tester = new CommandTester(new ConsumeOutboxCommand($consumer));
        $exitCode = $tester->execute(['--time-limit' => 5]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('EntityManager was closed', $tester->getDisplay());
    }
}
