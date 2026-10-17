<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Infrastructure\ApiPlatform\Processor\IssueApiTokenProcessor;
use App\Infrastructure\ApiPlatform\Processor\RefreshApiTokenProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Auth',
    operations: [
        new Post(
            uriTemplate: '/auth/token',
            processor: IssueApiTokenProcessor::class,
            status: 200,
            deserialize: true,
        ),
        new Post(
            uriTemplate: '/auth/refresh',
            processor: RefreshApiTokenProcessor::class,
            status: 200,
            deserialize: true,
        ),
    ],
)]
final class AuthResource
{
    #[ApiProperty(identifier: true, readable: false, writable: false)]
    public string $id = 'auth';

    #[Assert\NotBlank(groups: ['token'])]
    public ?string $clientId = null;

    #[Assert\NotBlank(groups: ['token'])]
    public ?string $clientSecret = null;

    #[Assert\NotBlank(groups: ['refresh'])]
    public ?string $refreshToken = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $accessToken = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $tokenType = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?int $expiresIn = null;

    /** @var string[] */
    #[ApiProperty(readable: true, writable: false)]
    public array $scopes = [];
}
