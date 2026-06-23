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
        $needsDerivedMetrics = $this->columns->needsDerivedMetrics($columnKeys);
        $rows = $this->reports->staffProgressSummary($period, $collegeId);

        $filename = "tqa-staff-report-{$period->id}.csv";

        return response()->streamDownload(function () use ($rows, $selectedColumns, $needsDerivedMetrics, $period) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, array_column($selectedColumns, 'label'));

            foreach ($rows as $row) {
                if ($needsDerivedMetrics) {
                    $analytics = $this->reports->staffAnalytics($row['staff'], $period);
                    $row['derived_metrics'] = collect($analytics['extractions'] ?? [])
                        ->keyBy('metric_id')
                        ->all();
                }

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
