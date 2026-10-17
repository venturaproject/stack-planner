<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:outbox:failed', description: 'List outbox events that exhausted their retries.')]
final class ListFailedOutboxEventsCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, event_type, attempts, occurred_at, failed_at, last_error FROM outbox_events WHERE failed_at IS NOT NULL ORDER BY failed_at DESC'
        );

        if ([] === $rows) {
            $io->success('No failed outbox events.');

            return Command::SUCCESS;
        }

        $io->table(
            ['id', 'event_type', 'attempts', 'occurred_at', 'failed_at', 'last_error'],
            array_map(static fn (array $row): array => [
                $row['id'],
                $row['event_type'],
                $row['attempts'],
                $row['occurred_at'],
                $row['failed_at'],
                \is_scalar($row['last_error'] ?? null) ? substr((string) $row['last_error'], 0, 120) : '',
            ], $rows),
        );
        $io->writeln(\sprintf('%d failed event(s). Reprocess with: app:outbox:requeue <id>, or app:outbox:requeue --all', \count($rows)));

        return Command::SUCCESS;
    }
}
