<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\RequestPasswordResetCommand;
use App\Application\Auth\Command\RequestPasswordResetHandler;
use App\Application\Auth\Security\PasswordResetLinkSigner;
use App\Application\Shared\Mail\MailerInterface;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class RequestPasswordResetHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function activeUser(string $id = 'user-1'): User
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        $user->setActive(true);
        self::setId($user, $id);

        return $user;
    }

    public function testSendsAResetEmailForAnExistingActiveUser(): void
    {
        $user = $this->activeUser();
        $users = $this->createMock(UserRepositoryInterface::class);
        $users->expects(self::once())->method('findByEmail')->with('me@example.com')->willReturn($user);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->with(
            'me@example.com',
            'Reset your password',
            'emails/password_reset.html.twig',
            self::callback(static function (array $context): bool {
                self::assertSame('me@example.com', $context['userEmail']);
                self::assertStringContainsString('https://app.example.com/reset-password?', $context['resetUrl']);

                return true;
            }),
        );

        $handler = new RequestPasswordResetHandler($users, $mailer, new PasswordResetLinkSigner('secret'), 'https://app.example.com');
        $handler->handle(new RequestPasswordResetCommand('me@example.com'));
    }

    public function testDoesNothingForAnUnknownEmail(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn(null);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $handler = new RequestPasswordResetHandler($users, $mailer, new PasswordResetLinkSigner('secret'), 'https://app.example.com');
        $handler->handle(new RequestPasswordResetCommand('nobody@example.com'));
    }

    public function testDoesNothingForADeactivatedUser(): void
    {
        $user = $this->activeUser();
        $user->setActive(false);
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $handler = new RequestPasswordResetHandler($users, $mailer, new PasswordResetLinkSigner('secret'), 'https://app.example.com');
        $handler->handle(new RequestPasswordResetCommand('me@example.com'));
    }
}
