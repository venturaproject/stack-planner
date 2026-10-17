<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:outbox:requeue', description: 'Reset a failed outbox event (or all of them) so app:outbox:consume picks it up again.')]
final class RequeueFailedOutboxEventCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::OPTIONAL, 'The outbox_events.id to requeue.')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Requeue every failed event instead of a single id.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getArgument('id');
        $all = (bool) $input->getOption('all');

        if (null === $id && !$all) {
            $io->error('Pass an event id, or --all to requeue every failed event.');

            return Command::INVALID;
        }

        // Resets attempts too: a fresh run gets the full retry budget again rather
        // than immediately re-failing on the attempt that pushed it over MAX_ATTEMPTS.
        $affected = $this->connection->executeStatement(
            'UPDATE outbox_events SET failed_at = NULL, attempts = 0, available_at = NOW(), last_error = NULL WHERE failed_at IS NOT NULL'
                .($all ? '' : ' AND id = :id'),
            $all ? [] : ['id' => $id],
        );

        if (0 === $affected) {
            $io->warning('No matching failed event found.');

            return Command::FAILURE;
        }

        $io->success(\sprintf('Requeued %d event(s).', $affected));

        return Command::SUCCESS;
    }
}
