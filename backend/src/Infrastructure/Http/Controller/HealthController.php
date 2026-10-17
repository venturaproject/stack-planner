<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * Meant for orchestrator liveness/readiness probes — public (see security.yaml's
 * access_control), so it must never leak anything beyond "is this dependency up".
 * Deliberately doesn't extend AbstractController: no container helpers are needed,
 * and staying container-free keeps this trivially unit-testable.
 */
#[Route('/api/health', methods: ['GET'])]
final class HealthController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(): JsonResponse
    {
        $checks = ['database' => $this->checkDatabase()];
        $healthy = !\in_array(false, $checks, true);

        return new JsonResponse(
            [
                'status' => $healthy ? 'ok' : 'error',
                'checks' => array_map(static fn (bool $ok) => $ok ? 'ok' : 'error', $checks),
            ],
            $healthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }

    private function checkDatabase(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
