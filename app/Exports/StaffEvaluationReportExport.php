<?php

namespace App\Exports;

use App\Models\EvaluationQuestion;
use App\Models\EvaluationScoreMetric;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffEvaluationReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    /**
     * @param  Collection<int, array>  $rows
     * @param  Collection<int, EvaluationScoreMetric>  $derivedMetricColumns
     * @param  Collection<int, EvaluationQuestion>  $reportQuestionColumns
     */
    public function __construct(
        private readonly Collection $rows,
        private readonly Collection $derivedMetricColumns,
        private readonly Collection $reportQuestionColumns,
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows->map(function (array $row) {
            $line = [
                $row['staff']->full_name_en,
                $row['staff']->email,
                $row['staff']->college?->name_en,
                $row['staff']->department?->name_en,
                $row['required'],
                $row['completed'],
                $row['percentage'] . '%',
                $row['average'] === null ? '' : number_format((float) $row['average'], 2),
            ];

            foreach ($this->reportQuestionColumns as $questionCol) {
                $line[] = $this->formatQuestionCell($row, $questionCol);
            }

            foreach ($this->derivedMetricColumns as $metricCol) {
                $line[] = $this->formatDerivedMetricCell($row, $metricCol);
            }

            return $line;
        });
    }

    public function headings(): array
    {
        $headings = [
            'Staff Name', 'Email', 'College', 'Department',
            'Required Evaluations', 'Completed Evaluations', 'Completion %', 'Average Score (1-5)',
        ];

        foreach ($this->reportQuestionColumns as $questionCol) {
            $headings[] = \Illuminate\Support\Str::limit($questionCol->text, 80);
        }

        foreach ($this->derivedMetricColumns as $metricCol) {
            $headings[] = $metricCol->name;
        }

        return $headings;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function formatQuestionCell(array $row, EvaluationQuestion $questionCol): string
    {
        $data = $row['question_values'][$questionCol->id] ?? null;

        if (! $data) {
            return '';
        }

        if ($data['type'] === 'text') {
            return (string) ($data['text'] ?? '');
        }

        if ($data['average'] === null) {
            return '';
        }

        return number_format((float) $data['average'], 2);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function formatDerivedMetricCell(array $row, EvaluationScoreMetric $metricCol): string
    {
        $metricData = $row['derived_metrics'][$metricCol->id] ?? null;

        if (! $metricData) {
            return '';
        }

        if (! empty($metricData['letter_grade'])) {
            $text = $metricData['letter_grade'];
            if (! empty($metricData['letter_range'])) {
                $text .= ' (' . $metricData['letter_range'] . ')';
            }

            return $text;
        }

        if ($metricData['value'] === null) {
            return '';
        }

        return number_format((float) $metricData['value'], 2);
    }
}
