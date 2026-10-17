<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:outbox:consume', description: 'Consume committed domain events from the transactional outbox.')]
final class ConsumeOutboxCommand extends Command
{
    public function __construct(private readonly OutboxConsumer $consumer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'Maximum run time in seconds.', 3600)
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Idle delay in milliseconds.', 250);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timeLimit = $input->getOption('time-limit');
        $sleepOption = $input->getOption('sleep');
        $deadline = time() + (is_numeric($timeLimit) ? (int) $timeLimit : 3600);
        $sleep = is_numeric($sleepOption) ? (int) $sleepOption : 250;

        try {
            while (time() < $deadline) {
                if (!$this->consumer->consumeOne()) {
                    usleep($sleep * 1000);
                }
            }
        } catch (OutboxWorkerMustRestartException $exception) {
            // Deliberately not caught-and-continued: see the exception's own doc
            // comment. Exiting non-zero here, under Supervisor's autorestart, is
            // the recovery — a fresh process gets a fresh EntityManager and no
            // stale repository references anywhere.
            $output->writeln(\sprintf('<error>%s</error>', $exception->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
