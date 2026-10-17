<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Issue\Command\UpdateIssueCommand;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\ApiPlatform\Resource\IssueResource;
use App\Infrastructure\ApiPlatform\UriVariable;
use InvalidArgumentException;

/** @implements ProcessorInterface<IssueResource, IssueResource> */
final class UpdateIssueProcessor implements ProcessorInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): IssueResource
    {
        /** @var IssueResource $data */
        if (null === $data->title || null === $data->type || null === $data->priority) {
            throw new InvalidArgumentException('Task title, type, and priority are required.');
        }

        $command = new UpdateIssueCommand(
            title: $data->title,
            type: $data->type,
            priority: $data->priority,
            description: $data->description,
            dueAt: $data->dueAt,
            epicId: $data->epicId,
            sprintId: $data->sprintId,
            storyPoints: $data->storyPoints,
            assigneeNames: $data->assigneeNames,
        );
        $command->id = UriVariable::int($uriVariables, 'id');

        /** @var array<string, mixed> $result */
        $result = $this->commandBus->dispatch($command);

        return IssueResource::fromArray($result);
    }
}
