<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class CertificateTemplate extends Model
{
    use SoftDeletes;

    public const CANVAS_WIDTH = 1123;

    public const CANVAS_HEIGHT = 794;

    protected $fillable = [
        'evaluation_period_id',
        'evaluation_form_id',
        'background_path',
        'layout',
        'canvas_width',
        'canvas_height',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'layout'        => 'array',
        'is_published'  => 'boolean',
        'canvas_width'  => 'integer',
        'canvas_height' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(EvaluationForm::class, 'evaluation_form_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function placedFields(): array
    {
        return $this->layout['fields'] ?? [];
    }

    public function backgroundUrl(): ?string
    {
        if (! $this->background_path) {
            return null;
        }

        return route('certificates.background', $this->evaluation_period_id);
    }

    public function backgroundAbsolutePath(): ?string
    {
        if (! $this->background_path) {
            return null;
        }

        $path = Storage::disk('local')->path($this->background_path);

        return is_file($path) ? $path : null;
    }
}
