<?php

namespace App\Policies;

use App\Models\StaffMember;
use App\Models\User;

class StaffMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('staff.manage') || $user->can('staff.manage_department');
    }

    public function view(User $user, StaffMember $staff): bool
    {
        return $user->can('staff.manage') || $this->managesDepartmentStaff($user, $staff);
    }

    public function create(User $user): bool
    {
        return $user->can('staff.manage') || $user->can('staff.manage_department');
    }

    public function update(User $user, StaffMember $staff): bool
    {
        return $user->can('staff.manage') || $this->managesDepartmentStaff($user, $staff);
    }

    public function delete(User $user, StaffMember $staff): bool
    {
        if ($user->can('staff.manage')) {
            return true;
        }

        if (! $this->managesDepartmentStaff($user, $staff)) {
            return false;
        }

        $department = $user->headedDepartment();

        return $department === null
            || (int) $department->head_staff_id !== (int) $staff->id;
    }

    public function import(User $user): bool
    {
        return $user->can('staff.import');
    }

    private function managesDepartmentStaff(User $user, StaffMember $staff): bool
    {
        if (! $user->can('staff.manage_department')) {
            return false;
        }

        $department = $user->headedDepartment();

        return $department !== null
            && (int) $staff->department_id === (int) $department->id;
    }
}
