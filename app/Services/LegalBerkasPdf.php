<?php

namespace App\Services;

use setasign\Fpdi\Tcpdf\Fpdi;

class LegalBerkasPdf
{
    public function append(Fpdi $pdf, string $path, bool $originalSize = false): void
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
            $pages = $pdf->setSourceFile($path);
            for ($page = 1; $page <= $pages; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $pdf->AddPage($size['orientation'], 'A4');
                $scale = min($pdf->getPageWidth() / $size['width'], $pdf->getPageHeight() / $size['height']);
                if ($originalSize) $scale = min(1, $scale);
                $w = $size['width'] * $scale;
                $h = $size['height'] * $scale;
                $pdf->useTemplate($template, ($pdf->getPageWidth() - $w) / 2, ($pdf->getPageHeight() - $h) / 2, $w, $h);
            }
        } else {
            [$width, $height] = getimagesize($path);
            $orientation = $width > $height ? 'L' : 'P';
            $pdf->AddPage($orientation, 'A4');
            $maxWidth = $pdf->getPageWidth();
            $maxHeight = $pdf->getPageHeight();
            $scale = min($maxWidth / $width, $maxHeight / $height);
            // Raster images use 96 DPI when no physical page size is available.
            if ($originalSize) $scale = min(25.4 / 96, $scale);
            $w = $width * $scale;
            $h = $height * $scale;
            $pdf->Image($path, ($pdf->getPageWidth() - $w) / 2, ($pdf->getPageHeight() - $h) / 2, $w, $h);
        }
    }

    public function create(): Fpdi
    {
        $pdf = new Fpdi;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        return $pdf;
    }
}
