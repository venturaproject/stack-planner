<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Export;

use Symfony\Component\HttpFoundation\Response;

/**
 * Builds a minimal HTML-table document served with an .xls extension and the
 * legacy Excel MIME type — Excel opens this natively without a spreadsheet
 * library dependency, which is the standard pragmatic approach for simple
 * tabular exports.
 */
final class XlsExporter
{
    /**
     * @param string[]             $headers
     * @param array<array<scalar>> $rows
     */
    public static function respond(array $headers, array $rows, string $filename): Response
    {
        $html = '<html><head><meta charset="UTF-8"></head><body><table border="1">';
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<th>'.htmlspecialchars($header, \ENT_QUOTES).'</th>';
        }
        $html .= '</tr>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $value = (string) $cell;
                // Spreadsheet programs evaluate leading formula characters even in HTML tables.
                if (preg_match('/^[=+\-@]/', $value)) {
                    $value = "'".$value;
                }
                $html .= '<td>'.htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8').'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        $response = new Response($html);
        $response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->headers->set('Content-Disposition', \sprintf('attachment; filename="%s"', $filename));
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
