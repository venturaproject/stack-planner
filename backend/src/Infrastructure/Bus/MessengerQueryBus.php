<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus;

use App\Application\Shared\Bus\QueryBusInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerQueryBus implements QueryBusInterface
{
    use HandleTrait;

    public function __construct(
        #[Autowire(service: 'query.bus')] MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function ask(object $query): mixed
    {
        try {
            return $this->handle($query);
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious() ?? $e;
        }
    }
}
