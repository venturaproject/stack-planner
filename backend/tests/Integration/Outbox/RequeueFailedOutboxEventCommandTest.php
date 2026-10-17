<?php

declare(strict_types=1);

namespace App\Tests\Integration\Outbox;

use App\Domain\Auth\Event\UserLoggedIn;
use App\Infrastructure\Outbox\RequeueFailedOutboxEventCommand;
use App\Tests\Support\DatabaseKernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class RequeueFailedOutboxEventCommandTest extends DatabaseKernelTestCase
{
    private function insertFailed(string $id): void
    {
        $this->em->getConnection()->insert('outbox_events', [
            'id' => $id,
            'event_type' => UserLoggedIn::class,
            'payload' => '{}',
            'context' => '{}',
            'occurred_at' => '2026-01-01 00:00:00+00',
            'available_at' => '2026-01-01 00:00:00+00',
            'attempts' => 10,
            'failed_at' => '2026-01-02 00:00:00+00',
            'last_error' => 'boom',
        ]);
    }

    private function row(string $id): array
    {
        return $this->em->getConnection()->fetchAssociative('SELECT * FROM outbox_events WHERE id = :id', ['id' => $id]);
    }

    public function testRequiresEitherAnIdOrTheAllFlag(): void
    {
        $tester = new CommandTester(new RequeueFailedOutboxEventCommand($this->em->getConnection()));

        self::assertSame(Command::INVALID, $tester->execute([]));
    }

    public function testRequeuesASingleEventById(): void
    {
        $this->insertFailed('66666666-6666-6666-6666-666666666666');
        $this->insertFailed('77777777-7777-7777-7777-777777777777');

        $tester = new CommandTester(new RequeueFailedOutboxEventCommand($this->em->getConnection()));
        self::assertSame(Command::SUCCESS, $tester->execute(['id' => '66666666-6666-6666-6666-666666666666']));

        $requeued = $this->row('66666666-6666-6666-6666-666666666666');
        self::assertNull($requeued['failed_at']);
        self::assertSame(0, $requeued['attempts']);
        self::assertNull($requeued['last_error']);

        $untouched = $this->row('77777777-7777-7777-7777-777777777777');
        self::assertNotNull($untouched['failed_at']);
    }

    public function testRequeuesEveryFailedEventWithAllFlag(): void
    {
        $this->insertFailed('88888888-8888-8888-8888-888888888888');
        $this->insertFailed('99999999-9999-9999-9999-999999999999');

        $tester = new CommandTester(new RequeueFailedOutboxEventCommand($this->em->getConnection()));
        self::assertSame(Command::SUCCESS, $tester->execute(['--all' => true]));

        self::assertNull($this->row('88888888-8888-8888-8888-888888888888')['failed_at']);
        self::assertNull($this->row('99999999-9999-9999-9999-999999999999')['failed_at']);
    }

    public function testFailsWhenNoMatchingFailedEventExists(): void
    {
        $tester = new CommandTester(new RequeueFailedOutboxEventCommand($this->em->getConnection()));

        self::assertSame(Command::FAILURE, $tester->execute(['id' => '00000000-0000-0000-0000-000000000000']));
    }
}
