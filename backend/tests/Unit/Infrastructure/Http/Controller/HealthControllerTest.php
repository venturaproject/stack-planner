<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Controller;

use App\Infrastructure\Http\Controller\HealthController;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

final class HealthControllerTest extends TestCase
{
    #[AllowMockObjectsWithoutExpectations]
    public function testReturns503WhenTheDatabaseIsUnreachable(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('executeQuery')->willThrowException(
            $this->createMock(ConnectionException::class)
        );

        $response = (new HealthController($connection))();

        self::assertSame(503, $response->getStatusCode());
        self::assertSame(
            ['status' => 'error', 'checks' => ['database' => 'error']],
            json_decode((string) $response->getContent(), true)
        );
    }
}
