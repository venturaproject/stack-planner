<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Application\Auth\Exception\InvalidCredentialsException;
use App\Domain\Shared\Exception\BusinessRuleException;
use App\Domain\Shared\Exception\EntityNotFoundException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener]
class ExceptionListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            // Thrown either by #[MapRequestPayload]'s own value resolver, or by a
            // controller building the same exception by hand for a body it maps
            // manually (see e.g. AuthController::activate) — one shape for every
            // request-validation failure across the API, instead of each
            // hand-rolled controller inventing its own.
            $previous = $exception->getPrevious();
            if ($previous instanceof ValidationFailedException) {
                $event->setResponse(new JsonResponse(
                    [
                        'message' => 'Validation failed',
                        'errors' => array_map(
                            static fn (ConstraintViolationInterface $violation): array => [
                                'field' => $violation->getPropertyPath(),
                                'message' => $violation->getMessage(),
                            ],
                            iterator_to_array($previous->getViolations()),
                        ),
                    ],
                    $exception->getStatusCode(),
                ));

                return;
            }

            $event->setResponse(new JsonResponse(
                ['message' => $exception->getMessage() ?: 'HTTP error'],
                $exception->getStatusCode(),
            ));

            return;
        }

        if ($exception instanceof AccessDeniedException) {
            $event->setResponse(new JsonResponse(['message' => 'Access denied'], 403));

            return;
        }

        if ($exception instanceof InvalidCredentialsException) {
            $event->setResponse(new JsonResponse(['message' => 'Unauthorized'], 401));

            return;
        }

        if ($exception instanceof AuthenticationException) {
            $event->setResponse(new JsonResponse(['message' => 'Unauthorized'], 401));

            return;
        }

        if ($exception instanceof EntityNotFoundException) {
            $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], 404));

            return;
        }

        if ($exception instanceof BusinessRuleException) {
            $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], 409));

            return;
        }

        if ($exception instanceof MissingConstructorArgumentsException
            || $exception instanceof NotNormalizableValueException
            || $exception instanceof InvalidArgumentException
        ) {
            $event->setResponse(new JsonResponse(['message' => $exception->getMessage() ?: 'Bad request'], 400));

            return;
        }

        $this->logger->error(\sprintf('Unhandled exception [%s]: %s', $exception::class, $exception->getMessage()), ['exception' => $exception]);
        $event->setResponse(new JsonResponse(['message' => 'Internal server error'], 500));
    }
}
