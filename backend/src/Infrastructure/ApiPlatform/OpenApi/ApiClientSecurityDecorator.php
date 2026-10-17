<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class ApiClientSecurityDecorator implements OpenApiFactoryInterface
{
    private const PUBLIC_PATHS = ['/api/v1/auth/token', '/api/v1/auth/refresh'];

    public function __construct(private readonly OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $securitySchemes = $openApi->getComponents()->getSecuritySchemes() ?? new ArrayObject();
        $securitySchemes['bearerAuth'] = new SecurityScheme(
            type: 'http',
            scheme: 'bearer',
            description: 'API Client bearer token. Obtain via `POST /api/v1/auth/token` using a client_id/client_secret pair issued from the internal admin panel, refresh via `POST /api/v1/auth/refresh`.',
        );

        $openApi = $openApi
            ->withComponents($openApi->getComponents()->withSecuritySchemes($securitySchemes))
            ->withSecurity([['bearerAuth' => []]]);

        // Auth endpoints are public — remove bearerAuth requirement from them
        $paths = $openApi->getPaths();
        foreach (self::PUBLIC_PATHS as $path) {
            $pathItem = $paths->getPath($path);
            if (null === $pathItem) {
                continue;
            }
            $post = $pathItem->getPost();
            if (null !== $post) {
                $paths->addPath($path, $pathItem->withPost($post->withSecurity([])));
            }
        }

        return $openApi;
    }
}
