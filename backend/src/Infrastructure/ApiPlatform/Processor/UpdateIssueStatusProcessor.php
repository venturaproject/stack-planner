<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Issue\Command\UpdateIssueStatusCommand;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\ApiPlatform\Input\TaskStatusInput;
use App\Infrastructure\ApiPlatform\Resource\IssueResource;
use App\Infrastructure\ApiPlatform\UriVariable;
use InvalidArgumentException;

/** @implements ProcessorInterface<TaskStatusInput, IssueResource> */
final class UpdateIssueStatusProcessor implements ProcessorInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): IssueResource
    {
        /** @var TaskStatusInput $data */
        if (null === $data->status) {
            throw new InvalidArgumentException('A valid task status is required.');
        }

        $command = new UpdateIssueStatusCommand($data->status);
        $command->id = UriVariable::int($uriVariables, 'id');
        /** @var array<string, mixed> $result */
        $result = $this->commandBus->dispatch($command);

        return IssueResource::fromArray($result);
    }
}
