<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\AuditLog\Query;

use App\Application\AuditLog\DTO\PagedAuditLogResponse;
use App\Application\AuditLog\Query\AuditLogReadModelInterface;
use App\Application\AuditLog\Query\ListAuditLogByUserHandler;
use App\Application\AuditLog\Query\ListAuditLogByUserQuery;
use PHPUnit\Framework\TestCase;

final class ListAuditLogByUserHandlerTest extends TestCase
{
    public function testDelegatesToRepositoryWithPagination(): void
    {
        $expected = new PagedAuditLogResponse([], 0, 1, 1, 20);

        $repository = $this->createMock(AuditLogReadModelInterface::class);
        $repository->expects(self::once())->method('findByActorId')
            ->with('user-1', 2, 20)
            ->willReturn($expected);

        $handler = new ListAuditLogByUserHandler($repository);

        $response = $handler->handle(new ListAuditLogByUserQuery('user-1', 2, 20));

        self::assertSame($expected, $response);
    }
}
