<?php

namespace App\Services\Reporting;

use App\Models\EvaluationPeriod;
use App\Models\StaffMember;
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
     * Build a zip archive with one staff evaluation PDF per file.
     *
     * @return string Absolute path to the zip file (caller should delete after send).
     */
    public function createZipForPeriod(EvaluationPeriod $period, ?int $collegeId = null): string
    {
        $staffIds = $this->reports->staffProgressSummary($period, $collegeId)
            ->map(fn (array $row) => $row['staff']->id);

        $staffMembers = StaffMember::query()
            ->with(['department.college'])
            ->whereIn('id', $staffIds)
            ->orderBy('full_name_en')
            ->get();

        if ($staffMembers->isEmpty()) {
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

        $usedNames = [];

        foreach ($staffMembers as $staff) {
            $pdfData   = $this->reports->staffEvaluatorPdfData($staff, $period);
            $pdfBinary = $this->pdf->render('reports.staff_pdf', compact('staff', 'period', 'pdfData'));
            $basename  = $this->uniquePdfFilename($staff, $usedNames);
            $zip->addFromString($basename, $pdfBinary);
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * @param  array<string, true>  $usedNames
     */
    private function uniquePdfFilename(StaffMember $staff, array &$usedNames): string
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
