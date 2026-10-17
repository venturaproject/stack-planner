<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Application\Customer\DTO\CustomerResponse;
use App\Infrastructure\ApiPlatform\Processor\RegisterCustomerProcessor;
use App\Infrastructure\ApiPlatform\Processor\UpdateCustomerAddressProcessor;
use App\Infrastructure\ApiPlatform\Provider\CustomerProvider;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Customer',
    operations: [
        new GetCollection(uriTemplate: '/customers', provider: CustomerProvider::class, security: "is_granted('ROLE_SCOPE_CUSTOMERS_READ')"),
        new Get(uriTemplate: '/customers/{id}', provider: CustomerProvider::class, security: "is_granted('ROLE_SCOPE_CUSTOMERS_READ')"),
        new Post(uriTemplate: '/customers', processor: RegisterCustomerProcessor::class, security: "is_granted('ROLE_SCOPE_CUSTOMERS_WRITE')"),
        new Patch(
            uriTemplate: '/customers/{id}/address',
            provider: CustomerProvider::class,
            processor: UpdateCustomerAddressProcessor::class,
            security: "is_granted('ROLE_SCOPE_CUSTOMERS_WRITE')",
        ),
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 20,
    paginationClientItemsPerPage: true,
    paginationMaximumItemsPerPage: 100,
)]
final class CustomerResource
{
    #[ApiProperty(identifier: true, writable: false)]
    public ?int $id = null;

    #[Assert\NotBlank]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    public ?string $addressStreet = null;
    public ?string $addressCity = null;
    public ?string $addressPostalCode = null;
    public ?string $addressCountry = null;

    #[ApiProperty(readable: true, writable: false)]
    public ?string $createdAt = null;

    public static function fromResponse(CustomerResponse $r): self
    {
        $resource = new self();
        $resource->id = $r->id;
        $resource->name = $r->name;
        $resource->email = $r->email;
        $resource->addressStreet = $r->addressStreet;
        $resource->addressCity = $r->addressCity;
        $resource->addressPostalCode = $r->addressPostalCode;
        $resource->addressCountry = $r->addressCountry;
        $resource->createdAt = $r->createdAt;

        return $resource;
    }
}
