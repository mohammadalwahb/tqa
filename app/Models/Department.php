<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Department extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'college_id',
        'name_en',
        'name_ku',
        'code',
        'description',
        'head_staff_id',
        'quality_coordinator_staff_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name_en', 'name_ku', 'college_id', 'head_staff_id', 'quality_coordinator_staff_id', 'is_active'])
            ->logOnlyDirty();
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'head_staff_id');
    }

    public function qualityCoordinator(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'quality_coordinator_staff_id');
    }

    public function committees(): HasMany
    {
        return $this->hasMany(Committee::class);
    }
}
