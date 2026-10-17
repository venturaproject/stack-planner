<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Issue\Command\CreateIssueCommand;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Infrastructure\ApiPlatform\Resource\IssueResource;
use LogicException;

/** @implements ProcessorInterface<IssueResource, IssueResource> */
final class CreateIssueProcessor implements ProcessorInterface
{
    public function __construct(private readonly CommandBusInterface $commandBus)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): IssueResource
    {
        /** @var IssueResource $data */
        if (null === $data->projectId || null === $data->title) {
            throw new LogicException('Expected issue input with projectId and title.');
        }

        /** @var array<string, mixed> $result */
        $result = $this->commandBus->dispatch(new CreateIssueCommand(
            projectId: $data->projectId,
            title: $data->title,
            priority: $data->priority ?? 'medium',
            dueAt: $data->dueAt,
            type: $data->type ?? 'task',
            storyPoints: $data->storyPoints,
            description: $data->description,
            epicId: $data->epicId,
            sprintId: $data->sprintId,
            assigneeNames: $data->assigneeNames,
        ));

        return IssueResource::fromArray($result);
    }
}
