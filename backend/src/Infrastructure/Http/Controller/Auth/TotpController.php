<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Auth;

use App\Application\Auth\Command\ConfirmTotpSetupCommand;
use App\Application\Auth\Command\DisableTotpCommand;
use App\Application\Auth\Command\SetUpTotpCommand;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\Http\Auth\AuthCookieFactory;
use App\Infrastructure\Http\Validation\ReadsJsonBody;
use App\Infrastructure\Http\Validation\ThrowsValidationFailure;
use App\Infrastructure\Security\Symfony\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth/totp')]
class TotpController extends AbstractController
{
    use ReadsJsonBody;
    use ThrowsValidationFailure;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly AuthCookieFactory $cookieFactory,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/setup', methods: ['POST'])]
    public function setup(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        return $this->json($this->commandBus->dispatch(new SetUpTotpCommand($user->domainUser()->getId() ?? '')));
    }

    #[Route('/confirm', methods: ['POST'])]
    public function confirm(
        #[CurrentUser] SecurityUser $user,
        Request $request,
        #[Autowire(service: 'limiter.totp_limiter')]
        RateLimiterFactory $totpLimiter,
    ): JsonResponse {
        $limiter = $totpLimiter->create($user->domainUser()->getId() ?? 'unknown');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['message' => 'Too many attempts. Try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $code = $this->stringField($this->decodeJsonBody($request), 'code');

        $cmd = new ConfirmTotpSetupCommand($user->domainUser()->getId() ?? '', $code);
        $errors = $this->validator->validate($cmd);
        if (\count($errors) > 0) {
            $this->throwValidationFailed($cmd, $errors);
        }

        $result = $this->commandBus->dispatch($cmd);

        // The access_token cookie set at login may still carry `must_setup_totp: true`
        // (JWT claims are immutable once issued) — mint a fresh one now that setup is
        // confirmed, so the mandatory-setup redirect doesn't fire again on this same
        // session (see Require2faEnforcementListener / the frontend's requiredStep guard).
        $response = $this->json($result);
        $this->cookieFactory->refreshAccessTokenCookie($response, $this->jwtManager->create($user));

        return $response;
    }

    #[Route('/disable', methods: ['POST'])]
    public function disable(
        #[CurrentUser] SecurityUser $user,
        Request $request,
        #[Autowire(service: 'limiter.totp_limiter')]
        RateLimiterFactory $totpLimiter,
    ): JsonResponse {
        $limiter = $totpLimiter->create($user->domainUser()->getId() ?? 'unknown');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['message' => 'Too many attempts. Try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $password = $this->stringField($this->decodeJsonBody($request), 'password');

        $cmd = new DisableTotpCommand($user->domainUser()->getId() ?? '', $password);
        $errors = $this->validator->validate($cmd);
        if (\count($errors) > 0) {
            $this->throwValidationFailed($cmd, $errors);
        }

        $this->commandBus->dispatch($cmd);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
