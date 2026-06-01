<?php

namespace App\Services\Staff;

use App\Models\CommitteeMember;
use App\Models\Evaluation;
use App\Models\StaffMember;
use App\Models\User;
use RuntimeException;

class StaffUserEmailService
{
    /**
     * User IDs that may keep the same email as this staff record (linked account + prior links).
     *
     * @return array<int, int>
     */
    public function linkedUserIds(StaffMember $staff): array
    {
        $ids = User::query()
            ->where('staff_member_id', $staff->id)
            ->pluck('id');

        if ($staff->user_id) {
            $ids->push($staff->user_id);
        }

        return $ids->unique()->values()->all();
    }

    public function emailTakenByAnotherAccount(string $email, StaffMember $staff): bool
    {
        return $this->blockingUsersForStaffEmail($email, $staff)->isNotEmpty();
    }

    /**
     * Active users holding $email that cannot be merged into this staff's account.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function blockingUsersForStaffEmail(string $email, StaffMember $staff): \Illuminate\Support\Collection
    {
        $email = mb_strtolower(trim($email));
        $canonical = $this->resolveCanonicalUser($staff);

        return User::query()
            ->where('email', $email)
            ->whereNull('deleted_at')
            ->whereNotIn('id', $this->linkedUserIds($staff))
            ->get()
            ->filter(function (User $user) use ($staff, $canonical): bool {
                if (! $canonical) {
                    return true;
                }

                return ! $this->duplicateMayBeReclaimed($user, $staff, $canonical);
            });
    }

    public function resolveCanonicalUser(StaffMember $staff): ?User
    {
        if ($staff->user_id) {
            return User::query()->whereKey($staff->user_id)->whereNull('deleted_at')->first();
        }

        return User::query()
            ->where('staff_member_id', $staff->id)
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Assign $email to the canonical user, merging stray accounts that belong to the same staff.
     */
    public function reclaimEmailForLinkedUser(User $canonicalUser, StaffMember $staff, string $email): void
    {
        $email = mb_strtolower(trim($email));

        $duplicates = User::query()
            ->where('email', $email)
            ->where('id', '!=', $canonicalUser->id)
            ->whereNull('deleted_at')
            ->get();

        foreach ($duplicates as $duplicate) {
            if (! $this->duplicateMayBeReclaimed($duplicate, $staff, $canonicalUser)) {
                throw new RuntimeException(__('messages.staff_user_email_conflict'));
            }

            $this->mergeDuplicateIntoCanonical($canonicalUser, $duplicate);
        }
    }

    public function duplicateMayBeReclaimed(User $duplicate, StaffMember $staff, User $canonicalUser): bool
    {
        if ($duplicate->isSuperAdmin()) {
            return false;
        }

        if ((int) $duplicate->staff_member_id === (int) $staff->id) {
            return true;
        }

        if ($duplicate->staff_member_id !== null) {
            return false;
        }

        return (int) $canonicalUser->staff_member_id === (int) $staff->id
            || (int) $staff->user_id === (int) $canonicalUser->id;
    }

    private function mergeDuplicateIntoCanonical(User $canonical, User $duplicate): void
    {
        if ($duplicate->google_id && ! $canonical->google_id) {
            $canonical->google_id = $duplicate->google_id;
        }

        if ($duplicate->avatar_url && ! $canonical->avatar_url) {
            $canonical->avatar_url = $duplicate->avatar_url;
        }

        if ($duplicate->email_verified_at && ! $canonical->email_verified_at) {
            $canonical->email_verified_at = $duplicate->email_verified_at;
        }

        foreach ($duplicate->roles as $role) {
            if (! $canonical->hasRole($role->name)) {
                $canonical->assignRole($role->name);
            }
        }

        CommitteeMember::query()
            ->where('user_id', $duplicate->id)
            ->update(['user_id' => $canonical->id]);

        Evaluation::query()
            ->where('evaluator_user_id', $duplicate->id)
            ->update(['evaluator_user_id' => $canonical->id]);

        $duplicate->forceDelete();
    }
}
