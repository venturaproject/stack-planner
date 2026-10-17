<?php

declare(strict_types=1);

namespace App\Tests\Integration\Outbox;

use App\Domain\Auth\Event\UserLoggedIn;
use App\Infrastructure\Outbox\ListFailedOutboxEventsCommand;
use App\Tests\Support\DatabaseKernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ListFailedOutboxEventsCommandTest extends DatabaseKernelTestCase
{
    public function testReportsNoFailedEventsWhenThereAreNone(): void
    {
        $tester = new CommandTester(new ListFailedOutboxEventsCommand($this->em->getConnection()));

        self::assertSame(0, $tester->execute([]));
        self::assertStringContainsString('No failed outbox events', $tester->getDisplay());
    }

    public function testListsFailedEvents(): void
    {
        $this->em->getConnection()->insert('outbox_events', [
            'id' => '55555555-5555-5555-5555-555555555555',
            'event_type' => UserLoggedIn::class,
            'payload' => '{}',
            'context' => '{}',
            'occurred_at' => '2026-01-01 00:00:00+00',
            'available_at' => '2026-01-01 00:00:00+00',
            'attempts' => 10,
            'failed_at' => '2026-01-02 00:00:00+00',
            'last_error' => 'validation failed: missing field',
        ]);

        $tester = new CommandTester(new ListFailedOutboxEventsCommand($this->em->getConnection()));
        $tester->execute([]);

        $display = $tester->getDisplay();
        self::assertStringContainsString('55555555-5555-5555-5555-555555555555', $display);
        self::assertStringContainsString(UserLoggedIn::class, $display);
        self::assertStringContainsString('validation failed', $display);
        self::assertStringContainsString('1 failed event(s)', $display);
    }
}
