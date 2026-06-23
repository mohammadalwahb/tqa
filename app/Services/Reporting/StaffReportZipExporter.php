<?php

namespace App\Services\Reporting;

use App\Models\EvaluationPeriod;
use App\Services\Pdf\DomPdfFontRegistrar;
use App\Services\Pdf\PdfDocumentBuilder;
use RuntimeException;
use ZipArchive;

class StaffReportZipExporter
{
    public function __construct(
        private readonly PdfDocumentBuilder $pdf,
        private readonly EvaluationReportService $reports,
    ) {
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{staff:\App\Models\StaffMember, required:int, completed:int, percentage:float, average:float|null, question_values:array<int, array<string, mixed>>}>
     */
    public function eligibleStaffRows(
        EvaluationPeriod $period,
        ?int $collegeId = null,
        bool $submittedOnly = true,
    ): \Illuminate\Support\Collection {
        $staffRows = $this->reports->staffProgressSummary($period, $collegeId);

        if ($submittedOnly) {
            $staffRows = $staffRows->filter(fn (array $row) => (int) $row['completed'] > 0);
        }

        return $staffRows;
    }

    /**
     * @return string Absolute path to the zip file (caller should delete after send).
     */
    public function createZipForPeriod(
        EvaluationPeriod $period,
        ?int $collegeId = null,
        bool $submittedOnly = true,
        ?array $staffIds = null,
    ): string {
        $staffRows = $this->eligibleStaffRows($period, $collegeId, $submittedOnly);

        if ($staffIds !== null) {
            $allowed = array_flip(array_map('intval', $staffIds));
            $staffRows = $staffRows->filter(fn (array $row) => isset($allowed[$row['staff']->id]));
        }

        if ($staffRows->isEmpty()) {
            throw new RuntimeException(__('super_admin_evaluations.zip_no_staff'));
        }

        $basePath = tempnam(sys_get_temp_dir(), 'tqa-staff-reports-');
        if ($basePath === false) {
            throw new RuntimeException(__('super_admin_evaluations.zip_create_failed'));
        }

        $zipPath = $basePath . '.zip';
        if (! @rename($basePath, $zipPath)) {
            @unlink($basePath);
            throw new RuntimeException(__('super_admin_evaluations.zip_create_failed'));
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($zipPath);
            throw new RuntimeException(__('super_admin_evaluations.zip_create_failed'));
        }

        DomPdfFontRegistrar::ensureFontMetricsInstalled();

        $usedNames = [];

        foreach ($staffRows as $row) {
            $staff = $row['staff'];
            $staff->loadMissing(['department.college']);
            $pdfData   = $this->reports->staffEvaluatorPdfData($staff, $period);
            $pdfBinary = $this->pdf->render('reports.staff_pdf', compact('staff', 'period', 'pdfData'));
            $zip->addFromString($this->uniquePdfFilename($staff, $usedNames), $pdfBinary);
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * @param  array<string, true>  $usedNames
     */
    private function uniquePdfFilename(\App\Models\StaffMember $staff, array &$usedNames): string
    {
        $base = sprintf('%03d-%s', $staff->id, str($staff->full_name_en)->slug('_'));
        $name = $base . '.pdf';
        $suffix = 2;

        while (isset($usedNames[$name])) {
            $name = $base . '-' . $suffix . '.pdf';
            $suffix++;
        }

        $usedNames[$name] = true;

        return $name;
    }
}
