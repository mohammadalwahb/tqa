<?php

namespace App\Services\Users;

use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Models\StaffMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Collection;

class UserAccessSyncService
{
    /** @var list<string> */
    private const COMMITTEE_ACCESS_ROLES = [
        RolePermissionSeeder::ROLE_LOCAL_COMMITTEE,
        RolePermissionSeeder::ROLE_HD_COMMITTEE,
        RolePermissionSeeder::ROLE_DEPARTMENT_HEAD,
        RolePermissionSeeder::ROLE_STAFF_MEMBER,
    ];

    public function sync(User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $expected = $this->expectedRolesForUser($user);

        foreach (self::COMMITTEE_ACCESS_ROLES as $roleName) {
            if (in_array($roleName, $expected, true)) {
                if (! $user->hasRole($roleName)) {
                    $user->assignRole($roleName);
                }
            } elseif ($user->hasRole($roleName)) {
                $user->removeRole($roleName);
            }
        }
    }

    public function userMayAccessSystem(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->roles()->exists();
    }

    /**
     * @return list<string>
     */
    private function expectedRolesForUser(User $user): array
    {
        $roles = [];

        if (! $user->staff_member_id) {
            return $roles;
        }

        $staffId = (int) $user->staff_member_id;

        if ($this->isActiveCommitteeMember($staffId, Committee::TYPE_LOCAL)) {
            $roles[] = RolePermissionSeeder::ROLE_LOCAL_COMMITTEE;
        }

        if ($this->isActiveCommitteeMember($staffId, Committee::TYPE_HD)) {
            $roles[] = RolePermissionSeeder::ROLE_HD_COMMITTEE;
        }

        if ($user->headedDepartment() !== null) {
            $roles[] = RolePermissionSeeder::ROLE_DEPARTMENT_HEAD;
        }

        if ($user->staff_member_id) {
            $roles[] = RolePermissionSeeder::ROLE_STAFF_MEMBER;
        }

        return $roles;
    }

    private function isActiveCommitteeMember(int $staffMemberId, string $committeeType): bool
    {
        return CommitteeMember::query()
            ->where('staff_member_id', $staffMemberId)
            ->whereHas('committee', function ($query) use ($committeeType) {
                $query->where('type', $committeeType)->where('is_active', true);
            })
            ->exists();
    }

    public function syncForStaff(StaffMember $staff): void
    {
        if (! $staff->user_id) {
            return;
        }

        $user = User::find($staff->user_id);

        if ($user) {
            $this->sync($user);
        }
    }
}
