<?php

namespace App\Services\Reporting;

use App\Models\EvaluationPeriod;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationScoreMetric;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportColumnCatalog
{
    public function __construct(
        private readonly EvaluationReportService $reports,
    ) {
    }

    /**
     * @return list<array{key:string, label:string, group:string}>
     */
    public function availableColumns(EvaluationPeriod $period): array
    {
        $columns = [
            ['key' => 'staff_name', 'label' => __('reports.col_staff_name'), 'group' => 'core'],
            ['key' => 'email', 'label' => __('reports.col_email'), 'group' => 'core'],
            ['key' => 'college', 'label' => __('common.college'), 'group' => 'core'],
            ['key' => 'department', 'label' => __('common.department'), 'group' => 'core'],
            ['key' => 'required', 'label' => __('reports.required'), 'group' => 'core'],
            ['key' => 'completed', 'label' => __('reports.completed'), 'group' => 'core'],
            ['key' => 'completion_pct', 'label' => __('reports.completion_pct'), 'group' => 'core'],
            ['key' => 'average_score', 'label' => __('reports.average_score'), 'group' => 'core'],
        ];

        foreach ($this->reports->reportQuestionColumns($period) as $question) {
            $columns[] = [
                'key'   => 'question:' . $question->id,
                'label' => Str::limit($question->text, 80),
                'group' => 'questions',
            ];
        }

        foreach ($this->reports->reportDerivedMetricColumns($period) as $metric) {
            $columns[] = [
                'key'   => 'metric:' . $metric->id,
                'label' => $metric->name,
                'group' => 'metrics',
            ];
        }

        return $columns;
    }

    /**
     * @param  list<string>  $requestedKeys
     * @return list<array{key:string, label:string, group:string}>
     */
    public function resolveColumns(EvaluationPeriod $period, array $requestedKeys): array
    {
        $catalog = collect($this->availableColumns($period))->keyBy('key');
        $resolved = [];

        foreach ($requestedKeys as $key) {
            $column = $catalog->get($key);
            if ($column) {
                $resolved[] = $column;
            }
        }

        return $resolved;
    }

    public function needsFullProgress(array $columnKeys): bool
    {
        return collect($columnKeys)->contains(fn (string $key) => str_starts_with($key, 'metric:'));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function valueForColumn(array $row, string $key): string
    {
        $staff = $row['staff'];

        return match (true) {
            $key === 'staff_name'     => (string) $staff->full_name_en,
            $key === 'email'          => (string) $staff->email,
            $key === 'college'        => (string) ($staff->department?->college?->name_en ?? ''),
            $key === 'department'     => (string) ($staff->department?->name_en ?? ''),
            $key === 'required'       => (string) $row['required'],
            $key === 'completed'      => (string) $row['completed'],
            $key === 'completion_pct' => $row['percentage'] . '%',
            $key === 'average_score'  => $row['average'] === null ? '' : number_format((float) $row['average'], 2),
            str_starts_with($key, 'question:') => $this->questionCellValue($row, (int) Str::after($key, 'question:')),
            str_starts_with($key, 'metric:')   => $this->metricCellValue($row, (int) Str::after($key, 'metric:')),
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function questionCellValue(array $row, int $questionId): string
    {
        $data = $row['question_values'][$questionId] ?? null;

        if (! $data) {
            return '';
        }

        if (($data['type'] ?? '') === 'text') {
            return (string) ($data['text'] ?? '');
        }

        if (($data['average'] ?? null) === null) {
            return '';
        }

        return number_format((float) $data['average'], 2);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function metricCellValue(array $row, int $metricId): string
    {
        $metricData = $row['derived_metrics'][$metricId] ?? null;

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

        if (($metricData['value'] ?? null) === null) {
            return '';
        }

        return number_format((float) $metricData['value'], 2);
    }
}
