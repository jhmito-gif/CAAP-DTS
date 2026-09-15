<?php

namespace App\Support;

use Barryvdh\DomPDF\PDF;
use Dompdf\Canvas;
use Dompdf\FontMetrics;

/**
 * Footer of form CAAP-ODG-CCS-001 r2 (Routing Action Slip), stamped on every
 * page as on the printed form: a double rule, then the form code, revision
 * date and "Page X of Y", right-aligned.
 *
 * Drawn on the canvas after rendering because dompdf cannot print the total
 * page count from CSS. The bottom page margin in pdfs/record.blade.php leaves
 * room for it.
 */
class RasFormFooter
{
    public const FORM_CODE = 'CAAP-ODG-CCS-001 r2';

    public const REVISION_DATE = '24 May 2024';

    public static function apply(PDF $pdf): void
    {
        // Render first so the page count is known; stream() and output() reuse it.
        $pdf->render();

        $pdf->getDomPDF()->getCanvas()->page_script(
            function (int $pageNumber, int $pageCount, Canvas $canvas, FontMetrics $fontMetrics) {
                $regular = $fontMetrics->getFont('times', 'normal');
                $bold = $fontMetrics->getFont('times', 'bold');
                $size = 6;
                $black = [0, 0, 0];

                // 28px side margins (21pt); the rule sits inside the 76px (57pt) bottom margin.
                $left = 21;
                $right = $canvas->get_width() - 21;
                $top = $canvas->get_height() - 50;

                // Double rule: heavy over light.
                $canvas->line($left, $top, $right, $top, $black, 1.5);
                $canvas->line($left, $top + 2.5, $right, $top + 2.5, $black, 0.6);

                // Right-aligned lines, each a run of [text, font] segments.
                $lines = [
                    [[self::FORM_CODE, $bold]],
                    [[self::REVISION_DATE, $regular]],
                    [['Page ', $regular], [(string) $pageNumber, $bold], [' of ', $regular], [(string) $pageCount, $bold]],
                ];

                $y = $top + 6;

                foreach ($lines as $segments) {
                    $x = $right - array_sum(array_map(
                        fn (array $segment) => $fontMetrics->getTextWidth($segment[0], $segment[1], $size),
                        $segments
                    ));

                    foreach ($segments as [$text, $font]) {
                        $canvas->text($x, $y, $text, $font, $size, $black);
                        $x += $fontMetrics->getTextWidth($text, $font, $size);
                    }

                    $y += $size * 1.3;
                }
            }
        );
    }
}
