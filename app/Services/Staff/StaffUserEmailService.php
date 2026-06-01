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

        return $this->usersHoldingEmail($email, $this->linkedUserIds($staff))
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
     * Remove any user rows (including soft-deleted) blocking $email for a new staff login.
     */
    public function releaseEmailForStaffAccount(string $email, StaffMember $staff): void
    {
        $email = mb_strtolower(trim($email));

        foreach ($this->usersHoldingEmail($email, []) as $holder) {
            if ($holder->isSuperAdmin()) {
                throw new RuntimeException(__('messages.staff_user_email_conflict'));
            }

            if ($holder->staff_member_id !== null
                && (int) $holder->staff_member_id !== (int) $staff->id) {
                throw new RuntimeException(__('messages.staff_user_email_conflict'));
            }

            if ($holder->trashed() || $holder->staff_member_id === null) {
                $holder->forceDelete();

                continue;
            }

            throw new RuntimeException(__('messages.staff_user_email_conflict'));
        }
    }

    /**
     * Assign $email to the canonical user, merging stray accounts that belong to the same staff.
     */
    public function reclaimEmailForLinkedUser(User $canonicalUser, StaffMember $staff, string $email): void
    {
        $email = mb_strtolower(trim($email));

        foreach ($this->usersHoldingEmail($email, [$canonicalUser->id]) as $duplicate) {
            if ($duplicate->trashed()) {
                if ($this->trashedHolderMayBeRemoved($duplicate, $staff)) {
                    $duplicate->forceDelete();

                    continue;
                }

                throw new RuntimeException(__('messages.staff_user_email_conflict'));
            }

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

        if ($duplicate->staff_member_id !== null
            && (int) $duplicate->staff_member_id !== (int) $staff->id) {
            return false;
        }

        return (int) $duplicate->staff_member_id === (int) $staff->id
            || (int) $staff->user_id === (int) $canonicalUser->id
            || (int) $canonicalUser->staff_member_id === (int) $staff->id;
    }

    private function trashedHolderMayBeRemoved(User $user, StaffMember $staff): bool
    {
        if ($user->isSuperAdmin()) {
            return false;
        }

        if ($user->staff_member_id !== null
            && (int) $user->staff_member_id !== (int) $staff->id) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, int>  $exceptUserIds
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function usersHoldingEmail(string $email, array $exceptUserIds): \Illuminate\Support\Collection
    {
        $email = mb_strtolower(trim($email));

        return User::withTrashed()
            ->where('email', $email)
            ->whereNotIn('id', $exceptUserIds)
            ->get();
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
