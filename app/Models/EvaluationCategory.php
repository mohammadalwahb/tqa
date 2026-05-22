<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'evaluation_form_id',
        'name',
        'description',
        'sort_order',
        'include_in_final_score',
    ];

    protected $casts = [
        'include_in_final_score' => 'boolean',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'evaluation_form_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(EvaluationQuestion::class)->orderBy('sort_order');
    }
}
