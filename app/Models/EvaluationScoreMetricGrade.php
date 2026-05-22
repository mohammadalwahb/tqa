<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationScoreMetricGrade extends Model
{
    protected $fillable = [
        'evaluation_score_metric_id',
        'academic_title',
        'title_sort_order',
        'label',
        'min_value',
        'max_value',
        'sort_order',
    ];

    protected $casts = [
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
    ];

    public function metric(): BelongsTo
    {
        return $this->belongsTo(EvaluationScoreMetric::class, 'evaluation_score_metric_id');
    }

    public function rangeLabel(): string
    {
        if ($this->max_value === null) {
            return rtrim(rtrim(number_format((float) $this->min_value, 2, '.', ''), '0'), '.') . '+';
        }

        $min = rtrim(rtrim(number_format((float) $this->min_value, 2, '.', ''), '0'), '.');
        $max = rtrim(rtrim(number_format((float) $this->max_value, 2, '.', ''), '0'), '.');

        return "{$min}-{$max}";
    }
}
