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
        return $this->makePdf($view, $data)->download($filename);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data): string
    {
        DomPdfFontRegistrar::ensureFontMetricsInstalled();

        $html = view($view, $data)->render();

        /** @var PDF $pdf */
        $pdf = app(PDF::class);
        if ($this->isCertificatePdfView($view)) {
            DomPdfFontRegistrar::prepareDompdfForCertificate($pdf->getDomPDF());
        } else {
            DomPdfFontRegistrar::prepareDompdf($pdf->getDomPDF());
        }
        $pdf->loadHTML($html, 'UTF-8');

        return (string) $pdf->setPaper('a4', 'landscape')->output();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function makePdf(string $view, array $data): PDF
    {
        DomPdfFontRegistrar::ensureFontMetricsInstalled();

        $html = view($view, $data)->render();

        /** @var PDF $pdf */
        $pdf = app(PDF::class);
        if ($this->isCertificatePdfView($view)) {
            DomPdfFontRegistrar::prepareDompdfForCertificate($pdf->getDomPDF());
        } else {
            DomPdfFontRegistrar::prepareDompdf($pdf->getDomPDF());
        }
        $pdf->loadHTML($html, 'UTF-8');

        return $pdf->setPaper('a4', 'landscape');
    }

    private function isCertificatePdfView(string $view): bool
    {
        return str_starts_with($view, 'certificates.');
    }
}
