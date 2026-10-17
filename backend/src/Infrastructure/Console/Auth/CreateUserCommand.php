<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\Auth;

use App\Application\Auth\Command\ActivateUserCommand;
use App\Application\Auth\Command\CreateUserCommand as CreateUserAppCommand;
use App\Application\Auth\DTO\UserResponse;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Domain\Auth\Exception\RoleNotFoundException;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Creates a user and activates it immediately with the given password, skipping
 * the normal signed-activation-link email flow (see ActivateUserHandler and
 * SendWelcomeEmailOnUserCreated). That flow exists so a human sets their own
 * password; a console command run by whoever already has shell access to this
 * container doesn't need that indirection — it reuses the exact same domain
 * invariants (CreateUserHandler + ActivateUserHandler) instead of duplicating
 * user-creation logic, it just chains both commands back to back.
 */
#[AsCommand(name: 'app:auth:create-user', description: 'Create and activate a user directly — for local testing/seeding, not for real onboarding.')]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly RoleRepositoryInterface $roles,
        private readonly int $generatedPasswordLength,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::OPTIONAL, 'Minimum 8 characters.')
            ->addOption('generate-password', null, InputOption::VALUE_NONE, 'Generate a secure password and print it once.')
            ->addOption('first-name', null, InputOption::VALUE_REQUIRED, 'First name.', 'Test')
            ->addOption('last-name', null, InputOption::VALUE_REQUIRED, 'Last name.', 'User')
            ->addOption('role', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Role name (repeatable, e.g. --role ADMIN --role USER).', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $password = $this->resolvePassword($input);

        $roleIds = [];
        $roleOption = $input->getOption('role');
        foreach ((\is_array($roleOption) ? $roleOption : []) as $roleName) {
            $roleName = \is_string($roleName) ? $roleName : '';
            $role = $this->roles->findByName($roleName) ?? throw new RoleNotFoundException();
            $roleIds[] = $role->getId() ?? throw new LogicException('Persisted role has no id.');
        }

        /** @var UserResponse $user */
        $user = $this->commandBus->dispatch(new CreateUserAppCommand(
            email: self::stringArgument($input, 'email'),
            firstName: self::stringOption($input, 'first-name'),
            lastName: self::stringOption($input, 'last-name'),
            roleIds: [] === $roleIds ? null : $roleIds,
        ));

        // Brand-new users start at activationVersion 0 (see Domain\Auth\Entity\User) —
        // no round-trip needed to look it up before activating.
        $this->commandBus->dispatch(new ActivateUserCommand($user->id, 0, $password));

        $io->success(\sprintf('Created and activated %s (id: %s).', $user->email, $user->id));
        if ($input->getOption('generate-password')) {
            $io->note('Generated password: '.$password);
        }

        return Command::SUCCESS;
    }

    private static function stringArgument(InputInterface $input, string $name): string
    {
        $value = $input->getArgument($name);

        return \is_string($value) ? $value : '';
    }

    private static function stringOption(InputInterface $input, string $name): string
    {
        $value = $input->getOption($name);

        return \is_string($value) ? $value : '';
    }

    private function resolvePassword(InputInterface $input): string
    {
        $password = $input->getArgument('password');
        $generatePassword = $input->getOption('generate-password');

        if ($generatePassword && null !== $password) {
            throw new InvalidArgumentException('Provide a password or use --generate-password, not both.');
        }

        if ($generatePassword) {
            if ($this->generatedPasswordLength < 8) {
                throw new LogicException('AUTH_GENERATED_PASSWORD_LENGTH must be at least 8.');
            }

            $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
            $generatedPassword = '';
            $lastIndex = \strlen($alphabet) - 1;

            for ($i = 0; $i < $this->generatedPasswordLength; ++$i) {
                $generatedPassword .= $alphabet[random_int(0, $lastIndex)];
            }

            return $generatedPassword;
        }

        if (!\is_string($password) || '' === $password) {
            throw new InvalidArgumentException('A password or --generate-password is required.');
        }

        return $password;
    }
}
