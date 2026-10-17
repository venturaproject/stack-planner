<?php

use App\Application\Auth\Command\LoginHandler;
use App\Application\Auth\Command\RefreshTokenHandler;
use App\Application\Auth\Command\UploadAvatarHandler;
use App\Application\Auth\EventHandler\OnUserCreatedHandler;
use App\Application\Auth\EventHandler\SendCredentialsEmailOnPasswordChanged;
use App\Application\Auth\EventHandler\SendWelcomeEmailOnUserCreated;
use App\Application\Auth\Security\AccessTokenIssuerInterface;
use App\Application\Auth\Security\PasswordHasherInterface;
use App\Application\Auth\Query\AuthReadModelInterface;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Domain\Auth\Event\UserCreated;
use App\Domain\Auth\Event\UserPasswordChanged;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Infrastructure\Bus\MessengerCommandBus;
use App\Infrastructure\Console\Auth\CreateUserCommand;
use App\Infrastructure\Bus\MessengerQueryBus;
use App\Infrastructure\Http\Auth\AuthCookieFactory;
use App\Infrastructure\Http\Security\JwtClaimsListener;
use App\Infrastructure\Security\Lexik\LexikAccessTokenIssuer;
use App\Infrastructure\Security\Symfony\SymfonyPasswordHasher;
use App\Infrastructure\Persistence\Doctrine\DoctrinePermissionRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineRefreshTokenRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineAuthReadModel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container): void {
    $services = $container->services();
    $container->parameters()->set('app.auth.generated_password_length', 15);

    // Repository aliases
    $services->alias(UserRepositoryInterface::class, DoctrineUserRepository::class);
    $services->alias(RoleRepositoryInterface::class, DoctrineRoleRepository::class);
    $services->alias(PermissionRepositoryInterface::class, DoctrinePermissionRepository::class);
    $services->alias(AuthReadModelInterface::class, DoctrineAuthReadModel::class);
    $services->alias(RefreshTokenRepositoryInterface::class, DoctrineRefreshTokenRepository::class);
    $services->alias(AccessTokenIssuerInterface::class, LexikAccessTokenIssuer::class);
    $services->alias(PasswordHasherInterface::class, SymfonyPasswordHasher::class);

    $services->set(\App\Application\Auth\Security\ActivationLinkSigner::class)
        ->arg('$secret', '%kernel.secret%');

    $services->set(\App\Application\Auth\Security\PasswordResetLinkSigner::class)
        ->arg('$secret', '%kernel.secret%');

    $services->set(\App\Application\Auth\Command\RequestPasswordResetHandler::class)
        ->autowire()->autoconfigure()
        ->arg('$appUrl', '%env(APP_URL)%');

    // Bus interfaces → implementations
    $services->alias(CommandBusInterface::class, MessengerCommandBus::class);
    $services->alias(QueryBusInterface::class, MessengerQueryBus::class);

    // Handlers with scalar params
    $services->set(LoginHandler::class)
        ->autowire()->autoconfigure()
        ->arg('$refreshTtlSecs', '%env(int:JWT_REFRESH_TTL_SECS)%');

    $services->set(RefreshTokenHandler::class)
        ->autowire()->autoconfigure();

    $services->set(UploadAvatarHandler::class)
        ->autowire()->autoconfigure();

    $services->set(CreateUserCommand::class)
        ->autowire()->autoconfigure()
        ->arg('$generatedPasswordLength', '%app.auth.generated_password_length%');

    $services->set(SendWelcomeEmailOnUserCreated::class)
        ->autowire()->autoconfigure()
        ->arg('$appUrl', '%env(APP_URL)%')
        ->tag('kernel.event_listener', ['event' => UserCreated::class, 'method' => 'handle']);

    $services->set(OnUserCreatedHandler::class)
        ->autowire()->autoconfigure()
        ->tag('kernel.event_listener', ['event' => UserCreated::class, 'method' => 'handle']);

    $services->set(SendCredentialsEmailOnPasswordChanged::class)
        ->autowire()->autoconfigure()
        ->tag('kernel.event_listener', ['event' => UserPasswordChanged::class, 'method' => 'handle']);

    $services->set(AuthCookieFactory::class)
        ->autowire()
        ->arg('$cookieSecure', '%env(bool:COOKIE_SECURE)%')
        ->arg('$sameSite', '%env(COOKIE_SAME_SITE)%');

    $services->set(JwtClaimsListener::class)
        ->autowire()->autoconfigure()
        ->arg('$appUrl', '%env(APP_URL)%');
};
