<?php
namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfExporter
{
    public function build(array $story): string
    {
        require_once APP_ROOT . '/vendor/dompdf/autoload.inc.php';
        $cache = APP_ROOT . '/storage/cache';

        if (!is_dir($cache)) {
            mkdir($cache, 0700, true);
        }

        $fallback = '';

        foreach ([12, 11.5, 11, 10.5] as $size) {
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', false);
            $options->set('isPhpEnabled', false);
            $options->set('isJavascriptEnabled', false);
            $options->set('isFontSubsettingEnabled', true);
            $options->set('tempDir', $cache);
            $options->set('fontCache', $cache);
            $pdf = new Dompdf($options);
            $pdf->setPaper('A4', 'portrait');
            $pdf->loadHtml($this->html($story, $size), 'UTF-8');
            $pdf->render();

            $count = $pdf->getCanvas()->get_page_count();

            if ($size === 12 || $count === (int) $story['page_count']) {
                $font = $pdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
                $pdf->getCanvas()->page_text(277, 810, '{PAGE_NUM} / {PAGE_COUNT}', $font, 9, [0.4, 0.4, 0.4]);
                $bytes = $pdf->output();

                if ($count === (int) $story['page_count']) {
                    return $bytes;
                }

                $fallback = $bytes;
            }

            unset($pdf);
            gc_collect_cycles();
        }

        return $fallback;
    }

    private function html(array $story, float $size): string
    {
        $html = '<!doctype html><html lang="ru"><head><meta charset="UTF-8"><style>@page{margin:20mm 20mm 22mm}body{font-family:"DejaVu Sans";font-size:' . $size . 'pt;line-height:1.35;color:#222}.page{page-break-before:always}.page:first-child{page-break-before:auto}h1{font-size:19pt;line-height:1.25;margin:0 0 3mm}h2{font-size:13pt;line-height:1.3;margin:0 0 3mm}p{text-align:justify;margin:0 0 2mm;text-indent:6mm}.meta{font-size:9pt;color:#666;margin-bottom:4mm;text-indent:0}</style></head><body>';

        foreach ($story['pages'] as $index => $page) {
            $html .= '<section class="page">';

            if ($index === 0) {
                $html .= '<h1>' . e($story['title']) . '</h1><p class="meta">' . e(genre_name($story['genre'])) . ' · ' . e($story['author']) . '</p>';
            }

            $html .= '<h2>' . e($page['heading']) . '</h2>' . story_paragraphs($page['text']) . '</section>';
        }

        return $html . '</body></html>';
    }
}
