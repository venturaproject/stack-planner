<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Middleware;

use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Customer\Exception\EmailAlreadyRegisteredException;
use App\Domain\Customer\ValueObject\Email;
use App\Infrastructure\Http\Middleware\ExceptionListener;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

final class ExceptionListenerTest extends TestCase
{
    private function event(Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new ExceptionEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $exception);
    }

    public function testHttpExceptionUsesItsOwnStatusCode(): void
    {
        $listener = new ExceptionListener($this->createStub(LoggerInterface::class));
        $event = $this->event(new NotFoundHttpException('Route not found'));

        $listener($event);

        self::assertSame(404, $event->getResponse()?->getStatusCode());
        self::assertSame('Route not found', json_decode((string) $event->getResponse()?->getContent(), true)['message']);
    }

    public function testAccessDeniedExceptionMapsTo403(): void
    {
        $listener = new ExceptionListener($this->createStub(LoggerInterface::class));
        $event = $this->event(new AccessDeniedException());

        $listener($event);

        self::assertSame(403, $event->getResponse()?->getStatusCode());
    }

    public function testAuthenticationExceptionMapsTo401(): void
    {
        $listener = new ExceptionListener($this->createStub(LoggerInterface::class));
        $event = $this->event(new AuthenticationException());

        $listener($event);

        self::assertSame(401, $event->getResponse()?->getStatusCode());
    }

    public function testEntityNotFoundExceptionMapsTo404(): void
    {
        $listener = new ExceptionListener($this->createStub(LoggerInterface::class));
        $event = $this->event(new UserNotFoundException());

        $listener($event);

        self::assertSame(404, $event->getResponse()?->getStatusCode());
    }

    public function testBusinessRuleExceptionMapsTo409(): void
    {
        $listener = new ExceptionListener($this->createStub(LoggerInterface::class));
        $event = $this->event(new EmailAlreadyRegisteredException(new Email('me@example.com')));

        $listener($event);

        self::assertSame(409, $event->getResponse()?->getStatusCode());
    }

    public function testInvalidArgumentExceptionMapsTo400(): void
    {
        $listener = new ExceptionListener($this->createStub(LoggerInterface::class));
        $event = $this->event(new InvalidArgumentException('Bad input'));

        $listener($event);

        self::assertSame(400, $event->getResponse()?->getStatusCode());
    }

    public function testUnknownExceptionLogsAndMapsTo500(): void
    {
        $exception = new RuntimeException('Something exploded');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with(
            self::stringContains('RuntimeException'),
            self::callback(static fn (array $context) => $context['exception'] === $exception),
        );

        $listener = new ExceptionListener($logger);
        $event = $this->event($exception);

        $listener($event);

        self::assertSame(500, $event->getResponse()?->getStatusCode());
        self::assertSame('Internal server error', json_decode((string) $event->getResponse()?->getContent(), true)['message']);
    }
}
