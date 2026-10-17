<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Search\Query;

use App\Application\Search\Query\SearchHandler;
use App\Application\Search\Query\SearchQuery;
use App\Application\Search\Query\SearchReadModelInterface;
use PHPUnit\Framework\TestCase;

final class SearchHandlerTest extends TestCase
{
    public function testReturnsEmptyResultWithoutQueryingForBlankTerms(): void
    {
        $readModel = $this->createMock(SearchReadModelInterface::class);
        $readModel->expects(self::never())->method('search');

        $result = (new SearchHandler($readModel))->handle(new SearchQuery('   '));

        self::assertSame(
            ['users' => [], 'roles' => [], 'permissions' => [], 'categories' => [], 'tags' => []],
            $result,
        );
    }

    public function testTrimsTermBeforeSearching(): void
    {
        $expected = ['users' => [], 'roles' => [], 'permissions' => [], 'categories' => [], 'tags' => []];
        $readModel = $this->createMock(SearchReadModelInterface::class);
        $readModel->expects(self::once())->method('search')->with('ada')->willReturn($expected);

        self::assertSame($expected, (new SearchHandler($readModel))->handle(new SearchQuery(' ada ')));
    }
}
