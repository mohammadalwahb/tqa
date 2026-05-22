<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

class EvaluationQuestion extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_RATING = 'rating';
    public const TYPE_TEXT   = 'text';
    public const TYPE_NUMBER = 'number';

    protected $fillable = [
        'evaluation_form_id',
        'evaluation_category_id',
        'text',
        'help_text',
        'type',
        'sort_order',
        'is_required',
        'is_enabled',
        'show_in_reports',
    ];

    protected $casts = [
        'is_required'     => 'boolean',
        'is_enabled'      => 'boolean',
        'show_in_reports' => 'boolean',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'evaluation_form_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EvaluationCategory::class, 'evaluation_category_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class);
    }

    public function visibleToRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'evaluation_question_role',
            'evaluation_question_id',
            'role_id'
        );
    }

    public function isRating(): bool
    {
        return $this->type === self::TYPE_RATING;
    }

    public function isNumber(): bool
    {
        return $this->type === self::TYPE_NUMBER;
    }

    public function isScorable(): bool
    {
        return in_array($this->type, [self::TYPE_RATING, self::TYPE_NUMBER], true);
    }
}
