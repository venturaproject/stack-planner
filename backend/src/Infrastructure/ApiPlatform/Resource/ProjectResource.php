<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Query;
use App\Infrastructure\ApiPlatform\Input\ProjectSearchCriteria;
use App\Infrastructure\ApiPlatform\Processor\ProjectSearchProcessor;
use App\Infrastructure\ApiPlatform\Provider\ProjectProvider;

#[ApiResource(
    shortName: 'Project',
    operations: [
        new GetCollection(uriTemplate: '/projects', provider: ProjectProvider::class, security: "is_granted('ROLE_SCOPE_PROJECTS_READ')"),
        new Query(
            uriTemplate: '/projects/search',
            input: ProjectSearchCriteria::class,
            read: false,
            deserialize: true,
            validate: true,
            write: true,
            processor: ProjectSearchProcessor::class,
            security: "is_granted('ROLE_SCOPE_PROJECTS_READ')",
            description: 'Search projects with criteria supplied in the QUERY request body.',
        ),
    ],
)]
final class ProjectResource
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    public ?string $name = null;

    public ?string $key = null;

    public ?string $description = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $status = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $coverImage = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $color = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $startDate = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $endDate = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?int $issueCount = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?int $openIssueCount = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?int $completedIssueCount = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $activeSprintName = null;

    /** @var array<mixed> */
    #[ApiProperty(readable: true, writable: false)]
    public array $members = [];

    #[ApiProperty(readable: true, writable: false)]
    public ?string $createdAt = null;

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $resource = new self();
        $resource->id = self::intValue($data, 'id');
        $resource->name = self::stringValue($data, 'name');
        $resource->key = self::stringValue($data, 'key');
        $resource->description = self::stringValue($data, 'description');
        $resource->status = self::stringValue($data, 'status');
        $resource->coverImage = self::stringValue($data, 'coverImage');
        $resource->color = self::stringValue($data, 'color');
        $resource->startDate = self::stringValue($data, 'startDate');
        $resource->endDate = self::stringValue($data, 'endDate');
        $resource->issueCount = self::intValue($data, 'issueCount');
        $resource->openIssueCount = self::intValue($data, 'openIssueCount');
        $resource->completedIssueCount = self::intValue($data, 'completedIssueCount');
        $resource->activeSprintName = self::stringValue($data, 'activeSprintName');
        $resource->members = isset($data['members']) && \is_array($data['members']) ? $data['members'] : [];
        $resource->createdAt = self::stringValue($data, 'createdAt');

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
}
