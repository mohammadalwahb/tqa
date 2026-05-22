<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StaffMember extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'college_id',
        'department_id',
        'staff_status_id',
        'full_name_en',
        'full_name_ku',
        'email',
        'gender',
        'date_of_birth',
        'age',
        'employee_type',
        'qualification',
        'academic_title',
        'position',
        'is_teaching_staff',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth'     => 'date',
        'is_teaching_staff' => 'boolean',
        'is_active'         => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'full_name_en', 'email', 'college_id', 'department_id',
                'staff_status_id', 'is_active', 'is_teaching_staff',
            ])
            ->logOnlyDirty();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StaffStatus::class, 'staff_status_id');
    }

    public function evaluationsReceived(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'evaluatee_staff_id');
    }

    public function isHeadOfDepartment(): bool
    {
        return $this->department && (int) $this->department->head_staff_id === (int) $this->id;
    }

    public function isDeanOfCollege(): bool
    {
        return $this->college && (int) $this->college->dean_staff_id === (int) $this->id;
    }
}
