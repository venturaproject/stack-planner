<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\EventHandler;

use App\Application\Auth\EventHandler\SendWelcomeEmailOnUserCreated;
use App\Application\Auth\Security\ActivationLinkSigner;
use App\Application\Shared\Mail\MailerInterface;
use App\Domain\Auth\Event\UserCreated;
use App\Infrastructure\Outbox\AuditContext;
use App\Infrastructure\Outbox\EmailIdempotencyGuard;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class SendWelcomeEmailOnUserCreatedTest extends TestCase
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

    public function testSendsAnEmailWithASignedActivationLink(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')
            ->with(
                'me@example.com',
                'Activate your account',
                'emails/welcome.html.twig',
                self::callback(static function (array $context): bool {
                    self::assertSame('me@example.com', $context['userEmail']);
                    self::assertStringStartsWith('https://app.example/api/auth/activate?', $context['activationUrl']);
                    self::assertStringContainsString('signature=', $context['activationUrl']);

                    return true;
                }),
            );

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(false);
        $connection->method('transactional')->willReturnCallback(static fn (callable $fn) => $fn());
        $connection->expects(self::once())->method('insert')->with('sent_emails', self::callback(static function (array $row): bool {
            self::assertSame('outbox-event-1', $row['outbox_event_id']);

            return true;
        }));

        $handler = new SendWelcomeEmailOnUserCreated(
            $mailer,
            new ActivationLinkSigner('test-secret'),
            'https://app.example',
            new EmailIdempotencyGuard($connection),
            $this->auditContext('outbox-event-1'),
        );

        $handler->handle(new UserCreated('user-1', 'me@example.com', 0));
    }

    public function testDoesNotResendForAnAlreadyProcessedOutboxEvent(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(1);
        $connection->expects(self::never())->method('insert');

        $handler = new SendWelcomeEmailOnUserCreated(
            $mailer,
            new ActivationLinkSigner('test-secret'),
            'https://app.example',
            new EmailIdempotencyGuard($connection),
            $this->auditContext('outbox-event-1'),
        );

        $handler->handle(new UserCreated('user-1', 'me@example.com', 0));
    }
}
