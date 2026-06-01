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
        if (! $this->shouldBeLocalEvaluatee($staff)) {
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

    /**
     * Remove pending evaluations for this staff member on local committees in their department.
     */
    public function removeStaffFromLocalCommitteeEvaluations(StaffMember $staff): int
    {
        if (! $staff->department_id) {
            return 0;
        }

        $committeeIds = Committee::query()
            ->where('type', Committee::TYPE_LOCAL)
            ->where('department_id', $staff->department_id)
            ->where('is_active', true)
            ->pluck('id');

        if ($committeeIds->isEmpty()) {
            return 0;
        }

        return Evaluation::query()
            ->where('evaluatee_staff_id', $staff->id)
            ->whereIn('committee_id', $committeeIds)
            ->delete();
    }

    /**
     * After create/update: add or remove local committee evaluations as appropriate.
     *
     * @return array{created: int, removed: int}
     */
    public function reconcileLocalEvaluationsForStaff(StaffMember $staff, ?int $previousDepartmentId = null): array
    {
        $removed = 0;

        if ($previousDepartmentId !== null
            && (int) $previousDepartmentId !== (int) $staff->department_id) {
            $removed += $this->removeStaffFromDepartmentCommitteeEvaluations($staff, $previousDepartmentId);
        }

        $removed += $this->removeStaffFromLocalCommitteeEvaluations($staff);

        $created = 0;
        if ($this->shouldBeLocalEvaluatee($staff)) {
            $created = $this->syncTeachingStaffToLocalCommittees($staff);
        }

        return ['created' => $created, 'removed' => $removed];
    }

    public function removeStaffFromDepartmentCommitteeEvaluations(StaffMember $staff, int $departmentId): int
    {
        $committeeIds = Committee::query()
            ->where('type', Committee::TYPE_LOCAL)
            ->where('department_id', $departmentId)
            ->where('is_active', true)
            ->pluck('id');

        if ($committeeIds->isEmpty()) {
            return 0;
        }

        return Evaluation::query()
            ->where('evaluatee_staff_id', $staff->id)
            ->whereIn('committee_id', $committeeIds)
            ->delete();
    }

    /**
     * Remove all evaluations where this staff member is the evaluatee (any committee).
     */
    public function removeAllEvaluationsForEvaluatee(StaffMember $staff): int
    {
        return Evaluation::query()
            ->where('evaluatee_staff_id', $staff->id)
            ->delete();
    }

    public function syncStaffToCommittee(Committee $committee, StaffMember $staff): int
    {
        if (! $committee->isLocal() || ! $committee->evaluation_form_id) {
            return 0;
        }

        if ((int) $committee->department_id !== (int) $staff->department_id) {
            return 0;
        }

        if (! $this->shouldBeLocalEvaluatee($staff)) {
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

    private function shouldBeLocalEvaluatee(StaffMember $staff): bool
    {
        if (! $staff->is_teaching_staff || ! $staff->is_active || ! $staff->department_id) {
            return false;
        }

        return ! LocalCommitteeEvaluateeResolver::isExcluded($staff, (int) $staff->department_id);
    }
}
