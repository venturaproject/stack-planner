<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Auth;

use App\Application\Auth\Command\ActivateUserCommand;
use App\Application\Auth\Command\LoginCommand;
use App\Application\Auth\Command\LoginResult;
use App\Application\Auth\Command\LogoutCommand;
use App\Application\Auth\Command\RefreshTokenCommand;
use App\Application\Auth\Command\RequestPasswordResetCommand;
use App\Application\Auth\Command\ResetPasswordCommand;
use App\Application\Auth\Command\StopImpersonatingCommand;
use App\Application\Auth\Command\UpdateProfileCommand;
use App\Application\Auth\Command\UploadAvatarCommand;
use App\Application\Auth\DTO\MeResponse;
use App\Application\Auth\DTO\UserResponse;
use App\Application\Auth\Security\ActivationLinkSigner;
use App\Application\Auth\Security\PasswordResetLinkSigner;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Domain\Auth\Exception\TotpRequiredException;
use App\Infrastructure\Http\Auth\AuthCookieFactory;
use App\Infrastructure\Http\Validation\ReadsJsonBody;
use App\Infrastructure\Http\Validation\ThrowsValidationFailure;
use App\Infrastructure\Security\Symfony\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    use ReadsJsonBody;
    use ThrowsValidationFailure;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly AuthCookieFactory $cookieFactory,
        private readonly ValidatorInterface $validator,
        private readonly Security $security,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ActivationLinkSigner $activationLinkSigner,
        private readonly PasswordResetLinkSigner $passwordResetLinkSigner,
    ) {
    }

    /** @return array<string, mixed>|null */
    private function currentJwtPayload(): ?array
    {
        $token = $this->security->getToken();
        if (null === $token) {
            return null;
        }

        $payload = $this->jwtManager->decode($token);

        return self::stringKeyed($payload);
    }

    /** @return array<string, mixed>|null */
    private static function stringKeyed(mixed $value): ?array
    {
        if (!\is_array($value)) {
            return null;
        }

        $result = [];
        foreach ($value as $key => $item) {
            $result[(string) $key] = $item;
        }

        return $result;
    }

    /**
     * The `impersonator_email` claim set by ImpersonateUserHandler, if the current
     * request is authenticated with an impersonation token — null otherwise.
     */
    private function impersonatorEmail(): ?string
    {
        $email = $this->currentJwtPayload()['impersonator_email'] ?? null;

        return \is_string($email) ? $email : null;
    }

    /**
     * The `must_setup_totp` claim set by LoginHandler when SecuritySettings.require2fa
     * is on and this user hasn't configured TOTP yet — see Require2faEnforcementListener,
     * which is what actually restricts the session; this just lets the frontend redirect
     * to the setup screen proactively instead of discovering it via a 403.
     */
    private function mustSetupTotp(): bool
    {
        return true === ($this->currentJwtPayload()['must_setup_totp'] ?? false);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(
        Request $request,
        #[Autowire(service: 'limiter.login_limiter')]
        RateLimiterFactory $loginLimiter,
        #[MapRequestPayload] LoginCommand $cmd,
    ): JsonResponse {
        $limiter = $loginLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['message' => 'Too many login attempts. Try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            /** @var LoginResult $result */
            $result = $this->commandBus->dispatch($cmd);
        } catch (TotpRequiredException) {
            return $this->json(['totpRequired' => true], Response::HTTP_UNAUTHORIZED);
        }

        $csrfToken = $this->cookieFactory->createCsrfToken();
        $response = $this->json(['user' => $result->auth->user, 'csrfToken' => $csrfToken]);
        $this->cookieFactory->setCookies($response, $result, $csrfToken);

        return $response;
    }

    #[Route('/activate', methods: ['POST'])]
    public function activate(
        Request $request,
        #[Autowire(service: 'limiter.activation_limiter')]
        RateLimiterFactory $activationLimiter,
    ): JsonResponse {
        $limiter = $activationLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['message' => 'Too many attempts. Try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $expires = $request->query->getInt('expires');
        $version = $request->query->getInt('version');
        $userId = $request->query->getString('user');
        $signature = $request->query->getString('signature');

        if ('' === $userId || !$this->activationLinkSigner->isValid($userId, $version, $expires, $signature)) {
            return $this->json(['message' => 'Invalid or expired activation link'], Response::HTTP_BAD_REQUEST);
        }

        $password = $this->stringField($this->decodeJsonBody($request), 'password');
        $cmd = new ActivateUserCommand($userId, $version, $password);
        $errors = $this->validator->validate($cmd);

        if (\count($errors) > 0) {
            $this->throwValidationFailed($cmd, $errors);
        }

        $this->commandBus->dispatch($cmd);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/forgot-password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        #[Autowire(service: 'limiter.forgot_password_limiter')]
        RateLimiterFactory $forgotPasswordLimiter,
    ): JsonResponse {
        $limiter = $forgotPasswordLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['message' => 'Too many attempts. Try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $email = $this->stringField($this->decodeJsonBody($request), 'email');
        $cmd = new RequestPasswordResetCommand($email);
        $errors = $this->validator->validate($cmd);

        // Same response whether validation passed or the email doesn't match anyone —
        // never give a caller a way to tell which emails exist in the system.
        if (0 === \count($errors)) {
            $this->commandBus->dispatch($cmd);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/reset-password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        #[Autowire(service: 'limiter.reset_password_limiter')]
        RateLimiterFactory $resetPasswordLimiter,
    ): JsonResponse {
        $limiter = $resetPasswordLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['message' => 'Too many attempts. Try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $expires = $request->query->getInt('expires');
        $version = $request->query->getInt('version');
        $userId = $request->query->getString('user');
        $signature = $request->query->getString('signature');

        if ('' === $userId || !$this->passwordResetLinkSigner->isValid($userId, $version, $expires, $signature)) {
            return $this->json(['message' => 'Invalid or expired reset link'], Response::HTTP_BAD_REQUEST);
        }

        $password = $this->stringField($this->decodeJsonBody($request), 'password');
        $cmd = new ResetPasswordCommand($userId, $version, $password);
        $errors = $this->validator->validate($cmd);

        if (\count($errors) > 0) {
            $this->throwValidationFailed($cmd, $errors);
        }

        $this->commandBus->dispatch($cmd);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/logout', methods: ['POST'])]
    public function logout(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        $this->commandBus->dispatch(new LogoutCommand($user->domainUser()->requireId()));
        $response = $this->json(null, Response::HTTP_NO_CONTENT);
        $this->cookieFactory->clearCookies($response);

        return $response;
    }

    #[Route('/csrf', methods: ['GET'])]
    public function csrf(): JsonResponse
    {
        $csrfToken = $this->cookieFactory->createCsrfToken();
        $response = $this->json(['csrfToken' => $csrfToken]);
        $this->cookieFactory->setCsrfCookie($response, $csrfToken);

        return $response;
    }

    #[Route('/refresh', methods: ['POST'])]
    public function refresh(
        Request $request,
        #[Autowire(service: 'limiter.refresh_token_limiter')]
        RateLimiterFactory $refreshTokenLimiter,
    ): JsonResponse {
        $limiter = $refreshTokenLimiter->create($request->getClientIp() ?? 'unknown');
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['message' => 'Too many requests. Try again later.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $tokenValue = $request->cookies->get('refresh_token');
        if (null === $tokenValue) {
            return $this->json(['message' => 'No refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var LoginResult $result */
        $result = $this->commandBus->dispatch(new RefreshTokenCommand($tokenValue));

        $csrfToken = $this->cookieFactory->createCsrfToken();
        $response = $this->json(['user' => $result->auth->user, 'csrfToken' => $csrfToken]);
        $this->cookieFactory->setCookies($response, $result, $csrfToken);

        return $response;
    }

    #[Route('/me', methods: ['GET'])]
    public function me(#[CurrentUser] SecurityUser $user): JsonResponse
    {
        return $this->json(new MeResponse(UserResponse::from($user->domainUser()), $this->impersonatorEmail(), $this->mustSetupTotp()));
    }

    #[Route('/stop-impersonating', methods: ['POST'])]
    public function stopImpersonating(): JsonResponse
    {
        $impersonatorEmail = $this->impersonatorEmail();
        if (null === $impersonatorEmail) {
            return $this->json(['message' => 'Not currently impersonating'], Response::HTTP_BAD_REQUEST);
        }

        /** @var LoginResult $result */
        $result = $this->commandBus->dispatch(new StopImpersonatingCommand($impersonatorEmail));

        $csrfToken = $this->cookieFactory->createCsrfToken();
        $response = $this->json(['user' => $result->auth->user, 'csrfToken' => $csrfToken]);
        $this->cookieFactory->setCookies($response, $result, $csrfToken);

        return $response;
    }

    #[Route('/me', methods: ['PATCH'])]
    public function updateMe(#[CurrentUser] SecurityUser $user, #[MapRequestPayload] UpdateProfileCommand $cmd): JsonResponse
    {
        $cmd->userId = $user->domainUser()->requireId();

        return $this->json($this->commandBus->dispatch($cmd));
    }

    #[Route('/me/avatar', methods: ['POST'])]
    public function uploadAvatar(#[CurrentUser] SecurityUser $user, Request $request): JsonResponse
    {
        $file = $request->files->get('avatar');

        if (!$file instanceof UploadedFile) {
            return $this->json(['message' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->commandBus->dispatch(
            new UploadAvatarCommand($user->domainUser()->requireId(), $file->getPathname(), $file->getClientOriginalExtension())
        ));
    }
}
