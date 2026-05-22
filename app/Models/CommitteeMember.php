<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommitteeMember extends Model
{
    use HasFactory, SoftDeletes;

    public const ROLE_QUALITY_COLLEGE_COORDINATOR = 'quality_college_coordinator';
    public const ROLE_SAME_DEPARTMENT_MEMBER      = 'same_department_member';
    public const ROLE_OTHER_DEPARTMENT_MEMBER     = 'other_department_member';
    public const ROLE_DEAN                        = 'dean';
    public const ROLE_HEAD_OTHER_DEPARTMENT       = 'head_other_department';
    public const ROLE_QUALITY_DEPARTMENT_COORD    = 'quality_department_coordinator';

    protected $fillable = [
        'committee_id',
        'user_id',
        'staff_member_id',
        'member_role',
        'source_department_id',
    ];

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function sourceDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'source_department_id');
    }

    public function displayName(): string
    {
        if ($this->staffMember) {
            return $this->staffMember->full_name_en;
        }
        if ($this->user) {
            return $this->user->name;
        }
        return 'Unknown';
    }
}
