<?php

namespace App\Services\Committees;

use App\Models\Committee;
use App\Models\Evaluation;
use App\Models\StaffMember;
use App\Services\Evaluations\SuperAdminEvaluationAssignmentService;
use Illuminate\Support\Collection;

class CommitteeEvaluationSyncService
{
    /**
     * Add draft evaluations for a teaching staff member on existing local committees
     * in their department (e.g. after HoD adds a colleague post-committee creation).
     */
    public function syncTeachingStaffToLocalCommittees(StaffMember $staff): int
    {
        if (! $staff->is_teaching_staff || ! $staff->is_active || ! $staff->department_id) {
            return 0;
        }

        $committees = Committee::query()
            ->where('type', Committee::TYPE_LOCAL)
            ->where('department_id', $staff->department_id)
            ->where('is_active', true)
            ->get();

        $created = 0;

        foreach ($committees as $committee) {
            $created += $this->syncStaffToCommittee($committee, $staff);
        }

        return $created;
    }

    public function syncStaffToCommittee(Committee $committee, StaffMember $staff): int
    {
        if (! $committee->isLocal() || ! $committee->evaluation_form_id) {
            return 0;
        }

        if ((int) $committee->department_id !== (int) $staff->department_id) {
            return 0;
        }

        if (! $staff->is_teaching_staff || ! $staff->is_active) {
            return 0;
        }

        if (LocalCommitteeEvaluateeResolver::isExcluded($staff, (int) $committee->department_id)) {
            return 0;
        }

        $evaluators = $committee->members()->whereNotNull('user_id')->get();
        $created    = 0;

        foreach ($evaluators as $member) {
            if ((int) $staff->id === (int) $member->staff_member_id) {
                continue;
            }

            $evaluation = Evaluation::firstOrCreate(
                [
                    'committee_id'         => $committee->id,
                    'evaluator_user_id'    => $member->user_id,
                    'evaluatee_staff_id'   => $staff->id,
                    'evaluation_period_id' => $committee->evaluation_period_id,
                ],
                [
                    'evaluation_form_id' => $committee->evaluation_form_id,
                    'status'             => Evaluation::STATUS_DRAFT,
                ]
            );

            if ($evaluation->wasRecentlyCreated) {
                $created++;
            }
        }

        $created += app(SuperAdminEvaluationAssignmentService::class)
            ->syncStaffOnCommittee($committee, $staff);

        return $created;
    }

    /**
     * @param  Collection<int, StaffMember>  $staffList
     */
    public function syncStaffListToCommittee(Committee $committee, Collection $staffList): int
    {
        $created = 0;

        foreach ($staffList as $staff) {
            $created += $this->syncStaffToCommittee($committee, $staff);
        }

        return $created;
    }
}
