<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Query;
use App\Infrastructure\ApiPlatform\Input\IssueSearchCriteria;
use App\Infrastructure\ApiPlatform\Input\TaskStatusInput;
use App\Infrastructure\ApiPlatform\Processor\CreateIssueProcessor;
use App\Infrastructure\ApiPlatform\Processor\IssueSearchProcessor;
use App\Infrastructure\ApiPlatform\Processor\UpdateIssueProcessor;
use App\Infrastructure\ApiPlatform\Processor\UpdateIssueStatusProcessor;
use App\Infrastructure\ApiPlatform\Provider\IssueProvider;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Task',
    operations: [
        new GetCollection(uriTemplate: '/tasks', provider: IssueProvider::class, security: "is_granted('ROLE_SCOPE_TASKS_READ')"),
        new Query(
            uriTemplate: '/tasks/search',
            input: IssueSearchCriteria::class,
            read: false,
            deserialize: true,
            validate: true,
            write: true,
            processor: IssueSearchProcessor::class,
            security: "is_granted('ROLE_SCOPE_TASKS_READ')",
            description: 'Search tasks with criteria supplied in the QUERY request body.',
        ),
        new Get(uriTemplate: '/tasks/{id}', provider: IssueProvider::class, security: "is_granted('ROLE_SCOPE_TASKS_READ')"),
        new Post(uriTemplate: '/tasks', processor: CreateIssueProcessor::class, security: "is_granted('ROLE_SCOPE_TASKS_WRITE')"),
        new Put(uriTemplate: '/tasks/{id}', processor: UpdateIssueProcessor::class, security: "is_granted('ROLE_SCOPE_TASKS_WRITE')"),
        new Patch(
            uriTemplate: '/tasks/{id}/status',
            input: TaskStatusInput::class,
            processor: UpdateIssueStatusProcessor::class,
            status: 200,
            security: "is_granted('ROLE_SCOPE_TASKS_WRITE')",
        ),
    ],
)]
final class IssueResource
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    #[Assert\Positive]
    public ?int $projectId = null;

    #[Assert\NotBlank]
    public ?string $title = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $key = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $status = null;

    public ?string $priority = 'medium';

    public ?string $type = 'task';

    public ?string $description = null;

    public ?string $dueAt = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $startAt = null;

    public ?int $epicId = null;

    public ?int $sprintId = null;

    public ?int $storyPoints = null;

    /** @var list<string> */
    public array $assigneeNames = [];

    #[ApiProperty(readable: true, writable: false)]
    public ?string $reporterName = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $createdAt = null;

    /** @var array<mixed> */
    #[ApiProperty(readable: true, writable: false)]
    public array $comments = [];

    /** @var array<mixed> */
    #[ApiProperty(readable: true, writable: false)]
    public array $checklist = [];

    /** @var array<mixed> */
    #[ApiProperty(readable: true, writable: false)]
    public array $worklogs = [];

    /** @var array<mixed> */
    #[ApiProperty(readable: true, writable: false)]
    public array $links = [];

    /** @var array<mixed> */
    #[ApiProperty(readable: true, writable: false)]
    public array $activities = [];

    /** @var array<mixed> */
    #[ApiProperty(readable: true, writable: false)]
    public array $attachments = [];

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $resource = new self();
        $resource->id = self::intValue($data, 'id');
        $resource->projectId = self::intValue($data, 'projectId');
        $resource->title = self::stringValue($data, 'title');
        $resource->key = self::stringValue($data, 'key');
        $resource->status = self::stringValue($data, 'status');
        $resource->priority = self::stringValue($data, 'priority');
        $resource->type = self::stringValue($data, 'type');
        $resource->description = self::stringValue($data, 'description');
        $resource->dueAt = self::stringValue($data, 'dueAt');
        $resource->startAt = self::stringValue($data, 'startAt');
        $resource->epicId = self::intValue($data, 'epicId');
        $resource->sprintId = self::intValue($data, 'sprintId');
        $resource->storyPoints = self::intValue($data, 'storyPoints');
        $assignees = $data['assigneeNames'] ?? null;
        if (\is_array($assignees)) {
            foreach ($assignees as $assignee) {
                if (\is_string($assignee)) {
                    $resource->assigneeNames[] = $assignee;
                }
            }
        }
        $resource->reporterName = self::stringValue($data, 'reporterName');
        $resource->createdAt = self::stringValue($data, 'createdAt');
        $resource->comments = self::arrayValue($data, 'comments');
        $resource->checklist = self::arrayValue($data, 'checklist');
        $resource->worklogs = self::arrayValue($data, 'worklogs');
        $resource->links = self::arrayValue($data, 'links');
        $resource->activities = self::arrayValue($data, 'activities');
        $resource->attachments = self::arrayValue($data, 'attachments');

        return $resource;
    }

    /** @param array<string, mixed> $data */
    private static function stringValue(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return \is_string($value) ? $value : null;
    }

    /** @param array<string, mixed> $data */
    private static function intValue(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;
        if (\is_int($value)) {
            return $value;
        }

        return \is_string($value) && is_numeric($value) ? (int) $value : null;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<mixed>
     */
    private static function arrayValue(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return \is_array($value) ? $value : [];
    }
}
