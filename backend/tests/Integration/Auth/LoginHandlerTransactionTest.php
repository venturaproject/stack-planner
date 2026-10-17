<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Application\Auth\Command\LoginCommand;
use App\Application\Auth\Security\PasswordHasherInterface;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Event\DomainEventPublisherInterface;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Shared\DomainEvent;
use App\Tests\Support\DatabaseKernelTestCase;
use RuntimeException;

/**
 * Verifies the command.bus doctrine_transaction middleware actually gives
 * LoginHandler atomicity between its two writes — the RefreshToken it persists
 * via its own repository, and the UserLoggedIn event OutboxPublisher inserts
 * separately. Before this middleware, those were two independent transactions:
 * a failure publishing the event left the refresh token committed with no
 * matching login event ever recorded. This test forces that second write to
 * fail and asserts the first one didn't silently survive it.
 */
final class LoginHandlerTransactionTest extends DatabaseKernelTestCase
{
    private function createActiveUser(string $email, string $password): User
    {
        $hasher = self::getContainer()->get(PasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setActive(true);
        $user->setPasswordHash($hasher->hash($user, $password));

        self::getContainer()->get(UserRepositoryInterface::class)->save($user);

        return $user;
    }

    private function refreshTokenCountFor(string $userId): int
    {
        return (int) $this->em->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM refresh_tokens WHERE user_id = :userId',
            ['userId' => $userId],
        );
    }

    public function testARefreshTokenIsNotLeftCommittedWhenPublishingTheLoginEventFails(): void
    {
        $user = $this->createActiveUser('atomic@example.com', 'correct-password');

        self::getContainer()->set(DomainEventPublisherInterface::class, new class implements DomainEventPublisherInterface {
            public function publish(DomainEvent $event): void
            {
                throw new RuntimeException('outbox insert failed');
            }
        });

        $bus = self::getContainer()->get(CommandBusInterface::class);

        try {
            $bus->dispatch(new LoginCommand('atomic@example.com', 'correct-password'));
            self::fail('Expected the publish() failure to propagate.');
        } catch (RuntimeException $e) {
            self::assertSame('outbox insert failed', $e->getMessage());
        }

        self::assertSame(
            0,
            $this->refreshTokenCountFor($user->requireId()),
            'The RefreshToken saved earlier in the same handle() call should have been rolled back along with the failed publish().',
        );
    }
}
