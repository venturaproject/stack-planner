<?php

declare(strict_types=1);

namespace App\Infrastructure\Bus;

use App\Application\Shared\Bus\CommandBusInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerCommandBus implements CommandBusInterface
{
    use HandleTrait;

    public function __construct(
        #[Autowire(service: 'command.bus')] MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function dispatch(object $command): mixed
    {
        try {
            return $this->handle($command);
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious() ?? $e;
        }
    }
}
