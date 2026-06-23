<?php

namespace App\Services\Pdf;

use App\Models\CertificateTemplate;
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

        return (string) $this->configurePaper($pdf, $view, $data)->output();
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

        return $this->configurePaper($pdf, $view, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function configurePaper(PDF $pdf, string $view, array $data): PDF
    {
        if ($this->isCertificatePdfView($view)) {
            [$widthPt, $heightPt] = $this->certificatePaperPoints($data);

            return $pdf->setPaper([0, 0, $widthPt, $heightPt]);
        }

        return $pdf->setPaper('a4', 'landscape');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: float, 1: float}
     */
    private function certificatePaperPoints(array $data): array
    {
        $widthPx = (float) ($data['width'] ?? CertificateTemplate::CANVAS_WIDTH);
        $heightPx = (float) ($data['height'] ?? CertificateTemplate::CANVAS_HEIGHT);
        $dpi = (float) config('dompdf.options.dpi', 96);

        return [
            $widthPx * 72 / $dpi,
            $heightPx * 72 / $dpi,
        ];
    }

    private function isCertificatePdfView(string $view): bool
    {
        return str_starts_with($view, 'certificates.');
    }
}
