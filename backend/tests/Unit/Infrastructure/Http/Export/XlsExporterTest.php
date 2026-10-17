<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Export;

use App\Infrastructure\Http\Export\XlsExporter;
use PHPUnit\Framework\TestCase;

final class XlsExporterTest extends TestCase
{
    public function testItEscapesHtmlAndNeutralizesSpreadsheetFormulas(): void
    {
        $response = XlsExporter::respond(['Name'], [['=<script>alert(1)</script>']], 'export.xls');

        self::assertSame('application/vnd.ms-excel; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertStringContainsString('<td>&#039;=&lt;script&gt;alert(1)&lt;/script&gt;</td>', (string) $response->getContent());
    }
}
