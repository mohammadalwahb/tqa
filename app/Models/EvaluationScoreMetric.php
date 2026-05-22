<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class EvaluationScoreMetric extends Model
{
    public const OPERATION_SUM     = 'sum';
    public const OPERATION_AVERAGE = 'average';

    protected $fillable = [
        'evaluation_form_id',
        'name',
        'operation',
        'show_in_reports',
        'grade_by_academic_title',
        'sort_order',
    ];

    protected $casts = [
        'show_in_reports'         => 'boolean',
        'grade_by_academic_title' => 'boolean',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'evaluation_form_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(
            EvaluationQuestion::class,
            'evaluation_score_metric_question',
            'evaluation_score_metric_id',
            'evaluation_question_id'
        );
    }

    public function grades(): HasMany
    {
        return $this->hasMany(EvaluationScoreMetricGrade::class)
            ->orderBy('title_sort_order')
            ->orderBy('sort_order');
    }

    public function customGrades(): HasMany
    {
        return $this->grades()->whereNull('academic_title');
    }

    /**
     * @return Collection<string, Collection<int, EvaluationScoreMetricGrade>>
     */
    public function gradesGroupedByAcademicTitle(): Collection
    {
        return $this->grades
            ->whereNotNull('academic_title')
            ->groupBy('academic_title')
            ->sortBy(fn ($group) => $group->first()?->title_sort_order ?? 0);
    }
}
