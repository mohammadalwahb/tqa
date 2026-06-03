<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Response;

class PdfDocumentBuilder
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function download(string $view, array $data, string $filename): Response
    {
        DomPdfFontRegistrar::ensureFontMetricsInstalled();

        $html = view($view, $data)->render();

        /** @var PDF $pdf */
        $pdf = app(PDF::class);
        DomPdfFontRegistrar::prepareDompdf($pdf->getDomPDF());
        $pdf->loadHTML($html, 'UTF-8');

        return $pdf->setPaper('a4', 'landscape')->download($filename);
    }
}
