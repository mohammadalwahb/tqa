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
        $columns = array_merge(
            $this->staffProfileColumns(),
            $this->evaluationProgressColumns(),
        );

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
     * @return list<array{key:string, label:string, group:string}>
     */
    private function staffProfileColumns(): array
    {
        return [
            ['key' => 'staff_name', 'label' => __('staff.full_name_en'), 'group' => 'staff'],
            ['key' => 'full_name_ku', 'label' => __('staff.full_name_ku'), 'group' => 'staff'],
            ['key' => 'email', 'label' => __('common.email'), 'group' => 'staff'],
            ['key' => 'gender', 'label' => __('staff.gender'), 'group' => 'staff'],
            ['key' => 'date_of_birth', 'label' => __('staff.date_of_birth'), 'group' => 'staff'],
            ['key' => 'age', 'label' => __('staff.age'), 'group' => 'staff'],
            ['key' => 'employee_type', 'label' => __('staff.employee_type'), 'group' => 'staff'],
            ['key' => 'college', 'label' => __('reports.col_college_en'), 'group' => 'staff'],
            ['key' => 'college_ku', 'label' => __('reports.col_college_ku'), 'group' => 'staff'],
            ['key' => 'department', 'label' => __('reports.col_department_en'), 'group' => 'staff'],
            ['key' => 'department_ku', 'label' => __('reports.col_department_ku'), 'group' => 'staff'],
            ['key' => 'qualification', 'label' => __('fields.qualification'), 'group' => 'staff'],
            ['key' => 'academic_title', 'label' => __('fields.academic_title'), 'group' => 'staff'],
            ['key' => 'position', 'label' => __('fields.position'), 'group' => 'staff'],
            ['key' => 'staff_status', 'label' => __('common.status'), 'group' => 'staff'],
            ['key' => 'is_teaching_staff', 'label' => __('staff.teaching_staff'), 'group' => 'staff'],
            ['key' => 'is_active', 'label' => __('common.active'), 'group' => 'staff'],
        ];
    }

    /**
     * @return list<array{key:string, label:string, group:string}>
     */
    private function evaluationProgressColumns(): array
    {
        return [
            ['key' => 'required', 'label' => __('reports.required'), 'group' => 'evaluation'],
            ['key' => 'completed', 'label' => __('reports.completed'), 'group' => 'evaluation'],
            ['key' => 'completion_pct', 'label' => __('reports.completion_pct'), 'group' => 'evaluation'],
            ['key' => 'average_score', 'label' => __('reports.average_score'), 'group' => 'evaluation'],
        ];
    }

    /**
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
        return $this->needsDerivedMetrics($columnKeys);
    }

    public function needsDerivedMetrics(array $columnKeys): bool
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
            $key === 'staff_name'        => (string) $staff->full_name_en,
            $key === 'full_name_ku'      => (string) ($staff->full_name_ku ?? ''),
            $key === 'email'             => (string) $staff->email,
            $key === 'gender'            => (string) ($staff->gender ?? ''),
            $key === 'date_of_birth'     => $this->formatDateValue($staff->getRawOriginal('date_of_birth')),
            $key === 'age'               => $staff->age === null ? '' : (string) $staff->age,
            $key === 'employee_type'     => (string) ($staff->employee_type ?? ''),
            $key === 'college'           => (string) ($staff->college?->name_en ?? $staff->department?->college?->name_en ?? ''),
            $key === 'college_ku'        => (string) ($staff->college?->name_ku ?? $staff->department?->college?->name_ku ?? ''),
            $key === 'department'        => (string) ($staff->department?->name_en ?? ''),
            $key === 'department_ku'     => (string) ($staff->department?->name_ku ?? ''),
            $key === 'qualification'     => (string) ($staff->qualification ?? ''),
            $key === 'academic_title'    => (string) ($staff->academic_title ?? ''),
            $key === 'position'          => (string) ($staff->position ?? ''),
            $key === 'staff_status'      => (string) ($staff->status?->name ?? ''),
            $key === 'is_teaching_staff' => $staff->is_teaching_staff ? '1' : '0',
            $key === 'is_active'         => $staff->is_active ? '1' : '0',
            $key === 'required'          => (string) $row['required'],
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
            return (string) $metricData['letter_grade'];
        }

        if (($metricData['value'] ?? null) === null) {
            return '';
        }

        return number_format((float) $metricData['value'], 2);
    }

    private function formatDateValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            return \Illuminate\Support\Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }
}
