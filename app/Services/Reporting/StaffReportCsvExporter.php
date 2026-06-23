<?php

namespace App\Services\Reporting;

use App\Models\EvaluationPeriod;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffReportCsvExporter
{
    public function __construct(
        private readonly EvaluationReportService $reports,
        private readonly ReportColumnCatalog $columns,
    ) {
    }

    /**
     * @param  list<array{key:string, label:string, group:string}>  $selectedColumns
     */
    public function download(
        EvaluationPeriod $period,
        array $selectedColumns,
        ?int $collegeId = null,
    ): StreamedResponse {
        $columnKeys = array_column($selectedColumns, 'key');
        $rows = $this->columns->needsFullProgress($columnKeys)
            ? $this->reports->staffProgress($period, $collegeId)
            : $this->reports->staffProgressSummary($period, $collegeId);

        $filename = "tqa-staff-report-{$period->id}.csv";

        return response()->streamDownload(function () use ($rows, $selectedColumns) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, array_column($selectedColumns, 'label'));

            foreach ($rows as $row) {
                $line = [];
                foreach ($selectedColumns as $column) {
                    $line[] = $this->columns->valueForColumn($row, $column['key']);
                }
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
