<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Validation;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * For the handful of actions that can't use #[MapRequestPayload] because their
 * command is built from more than just the request body (a route/query
 * parameter plus one body field, e.g. AuthController::activate) — keeps their
 * validation-failure response identical in shape and status to what that
 * resolver produces (see ExceptionListener, which unwraps ValidationFailedException
 * for both paths the same way).
 */
trait ThrowsValidationFailure
{
    private function throwValidationFailed(object $cmd, ConstraintViolationListInterface $errors): never
    {
        throw new UnprocessableEntityHttpException('Validation failed', new ValidationFailedException($cmd, $errors));
    }
}
