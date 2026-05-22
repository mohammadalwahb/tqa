<?php

namespace App\Policies;

use App\Models\StaffMember;
use App\Models\User;

class StaffMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('staff.manage') || $user->headedDepartment() !== null;
    }

    public function view(User $user, StaffMember $staff): bool
    {
        return $user->can('staff.manage') || $this->managesStaff($user, $staff);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, StaffMember $staff): bool
    {
        return $user->can('staff.manage') || $this->managesStaff($user, $staff);
    }

    public function delete(User $user, StaffMember $staff): bool
    {
        return $user->can('staff.manage');
    }

    public function import(User $user): bool
    {
        return $user->can('staff.import');
    }

    private function managesStaff(User $user, StaffMember $staff): bool
    {
        $department = $user->headedDepartment();

        return $department !== null
            && (int) $staff->department_id === (int) $department->id;
    }
}
