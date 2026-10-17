<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\EventHandler;

use App\Application\Auth\EventHandler\OnUserCreatedHandler;
use App\Application\Notification\Command\CreateNotificationCommand;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Domain\Auth\Event\UserCreated;
use App\Infrastructure\Outbox\AuditContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class OnUserCreatedHandlerTest extends TestCase
{
    private function auditContext(?string $outboxEventId): AuditContext
    {
        $context = new AuditContext(
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(RequestStack::class),
        );
        $context->setOutboxEventId($outboxEventId);

        return $context;
    }

    public function testDispatchesNotificationCommandWithUserEmail(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus->expects(self::once())->method('dispatch')
            ->with(self::callback(static function (CreateNotificationCommand $cmd) {
                self::assertSame('success', $cmd->type);
                self::assertNull($cmd->userId);
                self::assertStringContainsString('me@example.com', $cmd->message);

                return true;
            }));

        $handler = new OnUserCreatedHandler($commandBus, $this->auditContext(null));

        $handler->handle(new UserCreated('user-1', 'me@example.com', 0));
    }

    public function testPassesTheCurrentOutboxEventIdThroughForDedup(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $commandBus->expects(self::once())->method('dispatch')
            ->with(self::callback(static function (CreateNotificationCommand $cmd) {
                self::assertSame('outbox-event-1', $cmd->outboxEventId);

                return true;
            }));

        $handler = new OnUserCreatedHandler($commandBus, $this->auditContext('outbox-event-1'));

        $handler->handle(new UserCreated('user-1', 'me@example.com', 0));
    }
}
