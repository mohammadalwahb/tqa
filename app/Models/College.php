<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class College extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name_en',
        'name_ku',
        'code',
        'description',
        'dean_staff_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name_en', 'name_ku', 'code', 'is_active', 'dean_staff_id'])
            ->logOnlyDirty();
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }

    public function dean(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'dean_staff_id');
    }

    public function qualityCoordinators(): HasMany
    {
        return $this->hasMany(User::class, 'college_id');
    }

    public function committees(): HasMany
    {
        return $this->hasMany(Committee::class);
    }
}
