<?php

namespace App\Services\Evaluations;

use App\Models\Committee;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationForm;
use App\Models\StaffMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class SuperAdminEvaluationAssignmentService
{
    private ?int $superAdminRoleId = null;

    /**
     * Whether the form has enabled questions visible to the Super Admin role.
     */
    public function formHasSuperAdminQuestions(EvaluationForm $form): bool
    {
        $roleId = $this->superAdminRoleId();

        if (! $roleId) {
            return false;
        }

        $form->loadMissing(['questions.visibleToRoles']);

        return $form->questions
            ->where('is_enabled', true)
            ->contains(fn ($question) => $question->visibleToRoles->pluck('id')->contains($roleId));
    }

    /**
     * Create one shared draft evaluation per evaluatee (any Super Admin may fill it once).
     */
    public function syncForCommittee(Committee $committee): int
    {
        $committee->loadMissing(['form.questions.visibleToRoles']);

        if (! $committee->evaluation_form_id || ! $committee->form) {
            return 0;
        }

        if (! $this->formHasSuperAdminQuestions($committee->form)) {
            return 0;
        }

        return $this->createEvaluations($committee, $this->evaluateesForCommittee($committee));
    }

    /**
     * Ensure Super Admin evaluations exist for all active committees (idempotent).
     */
    public function syncAllActiveCommittees(): int
    {
        $created = 0;

        foreach (Committee::query()->where('is_active', true)->cursor() as $committee) {
            $created += $this->syncForCommittee($committee);
        }

        return $created;
    }

    /**
     * Sync Super Admin evaluations for every committee using this form.
     */
    public function syncForForm(EvaluationForm $form): int
    {
        if (! $this->formHasSuperAdminQuestions($form)) {
            return 0;
        }

        $committees = Committee::query()
            ->where('evaluation_form_id', $form->id)
            ->where('is_active', true)
            ->get();

        $created = 0;

        foreach ($committees as $committee) {
            $created += $this->syncForCommittee($committee);
        }

        return $created;
    }

    /**
     * Sync Super Admin evaluation for one staff member on a committee (e.g. newly added colleague).
     */
    public function syncStaffOnCommittee(Committee $committee, StaffMember $staff): int
    {
        $committee->loadMissing(['form.questions.visibleToRoles']);

        if (! $committee->evaluation_form_id || ! $committee->form) {
            return 0;
        }

        if (! $this->formHasSuperAdminQuestions($committee->form)) {
            return 0;
        }

        if (! $this->staffIsEvaluateeOnCommittee($committee, $staff)) {
            return 0;
        }

        return $this->createEvaluations($committee, collect([$staff]));
    }

    /**
     * True when any Super Admin may fill this evaluation (one institutional submission).
     */
    public function isSharedSuperAdminEvaluation(Evaluation $evaluation): bool
    {
        $evaluation->loadMissing('evaluator');

        return $evaluation->evaluator?->isSuperAdmin() ?? false;
    }

    /**
     * @param  Collection<int, StaffMember>  $evaluatees
     */
    private function createEvaluations(Committee $committee, Collection $evaluatees): int
    {
        $primaryAdmin  = $this->primarySuperAdminUser();
        $superAdminIds = $this->superAdminUserIds();

        if (! $primaryAdmin || $superAdminIds->isEmpty() || $evaluatees->isEmpty()) {
            return 0;
        }

        $created = 0;

        foreach ($evaluatees as $staff) {
            if ($this->shouldSkipPair($primaryAdmin, $staff)) {
                $this->deleteSuperAdminEvaluations($committee, $staff, $superAdminIds);

                continue;
            }

            $this->consolidateToSingleSuperAdminEvaluation($committee, $staff, $superAdminIds, $primaryAdmin);

            $evaluation = Evaluation::firstOrCreate(
                [
                    'committee_id'         => $committee->id,
                    'evaluator_user_id'    => $primaryAdmin->id,
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

        return $created;
    }

    /**
     * Keep at most one Super Admin evaluation per evaluatee; prefer an existing submission.
     */
    private function consolidateToSingleSuperAdminEvaluation(
        Committee $committee,
        StaffMember $staff,
        Collection $superAdminIds,
        User $primaryAdmin,
    ): void {
        $evaluations = Evaluation::query()
            ->where('committee_id', $committee->id)
            ->where('evaluatee_staff_id', $staff->id)
            ->where('evaluation_period_id', $committee->evaluation_period_id)
            ->whereIn('evaluator_user_id', $superAdminIds)
            ->get();

        if ($evaluations->isEmpty()) {
            return;
        }

        $keeper = $evaluations->first(fn (Evaluation $e) => $e->isSubmitted())
            ?? $evaluations->firstWhere('evaluator_user_id', $primaryAdmin->id)
            ?? $evaluations->sortBy('id')->first();

        foreach ($evaluations as $evaluation) {
            if ($evaluation->id !== $keeper->id) {
                $evaluation->delete();
            }
        }

        if ($keeper->isSubmitted() || (int) $keeper->evaluator_user_id === (int) $primaryAdmin->id) {
            return;
        }

        $keeper->delete();
    }

    /**
     * @param  Collection<int, int>  $superAdminIds
     */
    private function deleteSuperAdminEvaluations(Committee $committee, StaffMember $staff, Collection $superAdminIds): void
    {
        Evaluation::query()
            ->where('committee_id', $committee->id)
            ->where('evaluatee_staff_id', $staff->id)
            ->where('evaluation_period_id', $committee->evaluation_period_id)
            ->whereIn('evaluator_user_id', $superAdminIds)
            ->delete();
    }

    private function primarySuperAdminUser(): ?User
    {
        return User::query()
            ->role(RolePermissionSeeder::ROLE_SUPER_ADMIN)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * @return Collection<int, int>
     */
    private function superAdminUserIds(): Collection
    {
        return User::query()
            ->role(RolePermissionSeeder::ROLE_SUPER_ADMIN)
            ->where('is_active', true)
            ->pluck('id');
    }

    /**
     * @return Collection<int, StaffMember>
     */
    private function evaluateesForCommittee(Committee $committee): Collection
    {
        if ($committee->isHd()) {
            $department = Department::find($committee->department_id);

            if (! $department?->head_staff_id) {
                return collect();
            }

            $head = StaffMember::query()
                ->where('id', $department->head_staff_id)
                ->where('is_active', true)
                ->first();

            return $head ? collect([$head]) : collect();
        }

        return StaffMember::query()
            ->where('department_id', $committee->department_id)
            ->where('is_active', true)
            ->where('is_teaching_staff', true)
            ->get();
    }

    private function staffIsEvaluateeOnCommittee(Committee $committee, StaffMember $staff): bool
    {
        if ($committee->isHd()) {
            $department = Department::find($committee->department_id);

            return $department && (int) $department->head_staff_id === (int) $staff->id && $staff->is_active;
        }

        return $staff->is_teaching_staff
            && $staff->is_active
            && (int) $staff->department_id === (int) $committee->department_id;
    }

    private function shouldSkipPair(User $admin, StaffMember $staff): bool
    {
        if ($admin->staff_member_id && (int) $admin->staff_member_id === (int) $staff->id) {
            return true;
        }

        return $staff->email
            && strcasecmp((string) $admin->email, (string) $staff->email) === 0;
    }

    private function superAdminRoleId(): ?int
    {
        if ($this->superAdminRoleId !== null) {
            return $this->superAdminRoleId;
        }

        $this->superAdminRoleId = Role::query()
            ->where('name', RolePermissionSeeder::ROLE_SUPER_ADMIN)
            ->where('guard_name', 'web')
            ->value('id');

        return $this->superAdminRoleId ? (int) $this->superAdminRoleId : null;
    }
}
