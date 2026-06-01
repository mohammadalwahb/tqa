<?php

namespace App\Services\Committees;

use App\Models\Department;
use App\Models\StaffMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LocalCommitteeEvaluateeResolver
{
    /**
     * Teaching staff evaluated by a local committee (never the department head).
     *
     * @return Builder<StaffMember>
     */
    public static function queryForDepartment(int $departmentId): Builder
    {
        $headId = Department::whereKey($departmentId)->value('head_staff_id');

        return StaffMember::query()
            ->where('department_id', $departmentId)
            ->where('is_active', true)
            ->where('is_teaching_staff', true)
            ->when($headId, fn (Builder $query) => $query->where('id', '<>', $headId));
    }

    /**
     * @return Collection<int, StaffMember>
     */
    public static function forDepartment(int $departmentId): Collection
    {
        return self::queryForDepartment($departmentId)->get();
    }

    public static function isExcluded(StaffMember $staff, int $departmentId): bool
    {
        $headId = Department::whereKey($departmentId)->value('head_staff_id');

        return $headId && (int) $staff->id === (int) $headId;
    }
}
