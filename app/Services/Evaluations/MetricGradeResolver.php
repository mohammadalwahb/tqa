<?php

namespace App\Services\Evaluations;

use App\Models\EvaluationScoreMetric;
use App\Models\EvaluationScoreMetricGrade;
use App\Models\StaffMember;
use Illuminate\Support\Collection;

class MetricGradeResolver
{
    /**
     * @return array{label: string, range: string}|null
     */
    public function resolve(?float $value, EvaluationScoreMetric $metric, ?StaffMember $evaluatee = null): ?array
    {
        if ($value === null) {
            return null;
        }

        if ($metric->grade_by_academic_title) {
            $resolved = $this->resolveFromStoredTitleGrades($value, $metric, $evaluatee?->academic_title);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        $customGrades = $this->loadedGrades($metric)->filter(
            fn (EvaluationScoreMetricGrade $g) => trim((string) ($g->academic_title ?? '')) === ''
        );

        $resolved = $this->resolveFromGradeRows($value, $customGrades);
        if ($resolved !== null) {
            return $resolved;
        }

        if (trim((string) ($evaluatee?->academic_title ?? '')) !== '') {
            return $this->resolveFromStoredTitleGrades($value, $metric, $evaluatee->academic_title);
        }

        return null;
    }

    /**
     * @return array{label: string, range: string}|null
     */
    private function resolveFromStoredTitleGrades(?float $value, EvaluationScoreMetric $metric, ?string $academicTitle): ?array
    {
        $grades = $this->gradesForAcademicTitle($metric, $academicTitle);

        if ($grades->isEmpty()) {
            return null;
        }

        return $this->resolveFromGradeRows($value, $grades);
    }

    /**
     * @return Collection<int, EvaluationScoreMetricGrade>
     */
    private function gradesForAcademicTitle(EvaluationScoreMetric $metric, ?string $academicTitle): Collection
    {
        $titleGrades = $this->loadedGrades($metric)->filter(
            fn (EvaluationScoreMetricGrade $g) => trim((string) ($g->academic_title ?? '')) !== ''
        );

        if ($titleGrades->isEmpty()) {
            return collect();
        }

        $staffTitle = trim((string) $academicTitle);

        if ($staffTitle === '') {
            return collect();
        }

        $matched = $titleGrades->filter(
            fn (EvaluationScoreMetricGrade $g) => AcademicTitleMatcher::matches($g->academic_title, $staffTitle)
        );

        if ($matched->isNotEmpty()) {
            return $matched;
        }

        return $titleGrades->filter(
            fn (EvaluationScoreMetricGrade $g) => strcasecmp(trim((string) $g->academic_title), $staffTitle) === 0
        );
    }

    /**
     * @return Collection<int, EvaluationScoreMetricGrade>
     */
    private function loadedGrades(EvaluationScoreMetric $metric): Collection
    {
        return $metric->relationLoaded('grades')
            ? $metric->grades
            : $metric->grades()->get();
    }

    /**
     * @param  Collection<int, EvaluationScoreMetricGrade>|iterable<int, EvaluationScoreMetricGrade>  $grades
     * @return array{label: string, range: string}|null
     */
    private function resolveFromGradeRows(float $value, iterable $grades): ?array
    {
        foreach ($this->orderedGrades(collect($grades)) as $grade) {
            if ($this->matches($value, (float) $grade->min_value, $grade->max_value !== null ? (float) $grade->max_value : null)) {
                return [
                    'label' => $grade->label,
                    'range' => $grade->rangeLabel(),
                ];
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, EvaluationScoreMetricGrade>  $grades
     * @return Collection<int, EvaluationScoreMetricGrade>
     */
    private function orderedGrades(Collection $grades): Collection
    {
        return $grades->sortBy([
            ['min_value', 'desc'],
            ['sort_order', 'asc'],
        ])->values();
    }

    private function matches(float $value, float $min, ?float $max): bool
    {
        if ($value < $min) {
            return false;
        }

        if ($max === null) {
            return true;
        }

        return $value <= $max;
    }
}
