<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Console\Auth;

use App\Application\Auth\Command\ActivateUserCommand;
use App\Application\Auth\Command\CreateUserCommand as CreateUserAppCommand;
use App\Application\Auth\DTO\UserResponse;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use App\Infrastructure\Console\Auth\CreateUserCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateUserCommandTest extends TestCase
{
    public function testGeneratesAndUsesAPasswordWhenRequested(): void
    {
        $bus = new class implements CommandBusInterface {
            /** @var list<object> */
            public array $commands = [];

            public function dispatch(object $command): mixed
            {
                $this->commands[] = $command;

                if ($command instanceof CreateUserAppCommand) {
                    return new UserResponse('user-1', 'ada@example.com', 'Ada', 'Lovelace', null, false, 'en', [], [], '', '');
                }

                return null;
            }
        };
        $roles = $this->createStub(RoleRepositoryInterface::class);
        $tester = new CommandTester(new CreateUserCommand($bus, $roles, 15));

        self::assertSame(0, $tester->execute([
            'email' => 'ada@example.com',
            '--generate-password' => true,
        ]));

        self::assertInstanceOf(ActivateUserCommand::class, $bus->commands[1]);
        $activation = $bus->commands[1];
        self::assertSame(15, \strlen($activation->password));
        self::assertMatchesRegularExpression('/\A[ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789]+\z/', $activation->password);
        self::assertStringContainsString('Generated password: '.$activation->password, $tester->getDisplay());
    }
}
