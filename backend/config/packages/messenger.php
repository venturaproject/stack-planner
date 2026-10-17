<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'messenger' => [
            'default_bus' => 'command.bus',
            'buses' => [
                // Wraps every command handler's whole handle() call in one DB
                // transaction. Without this, a handler that does more than one write
                // — e.g. LoginHandler persisting a RefreshToken via its own
                // repository save() and then publish()-ing UserLoggedIn as a
                // separate raw outbox insert — has no atomicity between those
                // writes: a crash or failure between them leaves the first
                // committed and the second silently missing. (Aggregate-raised
                // events already got this for free — DoctrineOutboxListener writes
                // the outbox row from inside the same flush() that persists the
                // aggregate — this brings handlers using DomainEventPublisherInterface
                // directly, like LoginHandler/ImpersonateUserHandler, up to the same
                // guarantee instead of leaving them as a second, weaker one.)
                'command.bus' => ['middleware' => ['doctrine_transaction']],
                'query.bus' => null,
            ],
            'transports' => [
                // Backed by the `messenger_messages` table. Provision these queues with
                // `messenger:setup-transports` during deployment, before starting workers.
                'async' => [
                    'dsn' => '%env(MESSENGER_TRANSPORT_DSN)%',
                    'retry_strategy' => [
                        'max_retries' => 3,
                        'delay' => 1000,
                        'multiplier' => 2,
                        'max_delay' => 30000,
                    ],
                ],
                'failed' => 'doctrine://default?queue_name=failed',
            ],
            'failure_transport' => 'failed',
            'routing' => [
                // Emails sent via MailerInterface are queued instead of blocking the request.
                'Symfony\Component\Mailer\Messenger\SendEmailMessage' => 'async',
            ],
        ],
    ]);
};
