<?php

namespace App\Services\Committees;

use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationForm;
use App\Models\EvaluationPeriod;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\Evaluations\SuperAdminEvaluationAssignmentService;
use App\Services\Users\UserAccessSyncService;
use App\Support\LocaleHelper;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class CommitteeService
{
    /**
     * Create a local committee for a department.
     *
     * Members rules:
     * - Quality College Coordinator — automatically.
     * - Head of Department for the chosen department — automatically (when assigned).
     * - One staff member from the SAME department (not the head).
     * - One staff member from another department (any college in the university).
     *
     * @param array{
     *     department_id:int,
     *     same_department_member_id:int,
     *     other_department_member_id:int,
     *     evaluation_period_id:int,
     *     evaluation_form_id?:int|null,
     *     name?:string|null
     * } $data
     */
    public function createLocalCommittee(User $coordinator, array $data): Committee
    {
        $department = Department::with(['college', 'head'])->findOrFail($data['department_id']);
        $period     = EvaluationPeriod::findOrFail($data['evaluation_period_id']);
        $form       = isset($data['evaluation_form_id']) && $data['evaluation_form_id']
            ? EvaluationForm::findOrFail($data['evaluation_form_id'])
            : EvaluationForm::where('target_type', 'staff')->where('is_active', true)->first();

        if (! $coordinator->college_id || (int) $coordinator->college_id !== (int) $department->college_id) {
            throw new RuntimeException(__('messages.committee_own_college_only'));
        }

        $sameDeptStaffId  = (int) $data['same_department_member_id'];
        $otherDeptStaffId = (int) $data['other_department_member_id'];

        if ($sameDeptStaffId === $otherDeptStaffId) {
            throw new RuntimeException(__('messages.committee_members_must_differ'));
        }

        $head = $department->head;
        if (! $department->head_staff_id || ! $head || ! $head->is_active) {
            throw new RuntimeException(__('messages.committee_local_no_head'));
        }

        if (in_array((int) $head->id, [$sameDeptStaffId, $otherDeptStaffId], true)) {
            throw new RuntimeException(__('messages.committee_head_cannot_be_selected'));
        }

        $sameDeptStaff = StaffMember::findOrFail($sameDeptStaffId);
        if ((int) $sameDeptStaff->department_id !== (int) $department->id) {
            throw new RuntimeException(__('messages.committee_same_dept_required'));
        }

        $otherDeptStaff = StaffMember::findOrFail($otherDeptStaffId);
        $this->assertValidOtherDepartmentMember($otherDeptStaff, $department);

        return DB::transaction(function () use ($coordinator, $department, $period, $form, $head, $sameDeptStaff, $otherDeptStaff, $data) {
            $exists = Committee::where('type', Committee::TYPE_LOCAL)
                ->where('department_id', $department->id)
                ->where('evaluation_period_id', $period->id)
                ->whereNull('deleted_at')
                ->first();

            if ($exists) {
                throw new RuntimeException(__('messages.committee_local_exists'));
            }

            $committee = Committee::create([
                'type'                 => Committee::TYPE_LOCAL,
                'name'                 => $data['name'] ?? "Local committee · {$department->name_en}",
                'college_id'           => $department->college_id,
                'department_id'        => $department->id,
                'evaluation_period_id' => $period->id,
                'evaluation_form_id'   => $form?->id,
                'created_by'           => $coordinator->id,
                'is_active'            => true,
            ]);

            CommitteeMember::create([
                'committee_id'         => $committee->id,
                'user_id'              => $coordinator->id,
                'staff_member_id'      => $coordinator->staff_member_id,
                'member_role'          => CommitteeMember::ROLE_QUALITY_COLLEGE_COORDINATOR,
                'source_department_id' => null,
            ]);

            $this->addStaffMemberToCommittee(
                $committee,
                $head,
                CommitteeMember::ROLE_HEAD_OF_DEPARTMENT,
                $department->id,
            );

            $this->addStaffMemberToCommittee(
                $committee,
                $sameDeptStaff,
                CommitteeMember::ROLE_SAME_DEPARTMENT_MEMBER,
                $department->id,
            );

            $this->addStaffMemberToCommittee(
                $committee,
                $otherDeptStaff,
                CommitteeMember::ROLE_OTHER_DEPARTMENT_MEMBER,
                $otherDeptStaff->department_id,
            );

            $this->seedLocalEvaluations($committee);
            app(SuperAdminEvaluationAssignmentService::class)->syncForCommittee($committee);
            $this->grantCommitteeRoles($committee, RolePermissionSeeder::ROLE_LOCAL_COMMITTEE);

            return $committee->load('members.staffMember', 'members.user');
        });
    }

    /**
     * Create a dedicated Head of Department evaluation committee.
     *
     * One committee per department head. Members:
     * - Dean of the college (auto-filled if assigned)
     * - Quality College Coordinator (the current user)
     * - One staff member from the same department
     * - One external member from any college (may be from the same department)
     *
     * @param array{
     *     department_id:int,
     *     same_department_member_id:int,
     *     other_department_member_id:int,
     *     evaluation_period_id:int,
     *     evaluation_form_id?:int|null,
     *     name?:string|null
     * } $data
     */
    public function createHdCommittee(User $coordinator, array $data): Committee
    {
        $department = Department::with('college.dean')->findOrFail($data['department_id']);
        $college    = $department->college;

        if (! $department->head_staff_id) {
            throw new RuntimeException(__('messages.committee_hd_no_head'));
        }
        if (! $college?->dean_staff_id) {
            throw new RuntimeException(__('messages.committee_hd_no_dean'));
        }

        if (! $coordinator->college_id || (int) $coordinator->college_id !== (int) $college->id) {
            throw new RuntimeException(__('messages.committee_own_college_only'));
        }

        $sameDeptStaffId  = (int) $data['same_department_member_id'];
        $otherDeptStaffId = (int) $data['other_department_member_id'];

        if ($sameDeptStaffId === $otherDeptStaffId) {
            throw new RuntimeException(__('messages.committee_members_must_differ'));
        }

        $sameDeptStaff = StaffMember::findOrFail($sameDeptStaffId);
        if ((int) $sameDeptStaff->department_id !== (int) $department->id) {
            throw new RuntimeException(__('messages.committee_same_dept_required'));
        }
        if ((int) $sameDeptStaff->id === (int) $department->head_staff_id) {
            throw new RuntimeException(__('messages.committee_head_is_evaluatee'));
        }

        $otherDeptStaff = StaffMember::findOrFail($otherDeptStaffId);
        $this->assertValidHdExternalMember($otherDeptStaff, $department);

        $period = EvaluationPeriod::findOrFail($data['evaluation_period_id']);
        $form   = isset($data['evaluation_form_id']) && $data['evaluation_form_id']
            ? EvaluationForm::findOrFail($data['evaluation_form_id'])
            : EvaluationForm::where('is_active', true)->orderByDesc('id')->first();

        return DB::transaction(function () use ($coordinator, $department, $college, $sameDeptStaff, $otherDeptStaff, $period, $form, $data) {
            $exists = Committee::where('type', Committee::TYPE_HD)
                ->where('department_id', $department->id)
                ->where('evaluation_period_id', $period->id)
                ->whereNull('deleted_at')
                ->first();

            if ($exists) {
                throw new RuntimeException(__('messages.committee_hd_exists'));
            }

            $committee = Committee::create([
                'type'                 => Committee::TYPE_HD,
                'name'                 => $data['name'] ?? "HD committee · {$department->name_en}",
                'college_id'           => $college->id,
                'department_id'        => $department->id,
                'evaluation_period_id' => $period->id,
                'evaluation_form_id'   => $form?->id,
                'created_by'           => $coordinator->id,
                'is_active'            => true,
            ]);

            $this->addStaffMemberToCommittee(
                $committee,
                $college->dean,
                CommitteeMember::ROLE_DEAN,
                $college->dean?->department_id,
            );

            CommitteeMember::create([
                'committee_id'         => $committee->id,
                'user_id'              => $coordinator->id,
                'staff_member_id'      => $coordinator->staff_member_id,
                'member_role'          => CommitteeMember::ROLE_QUALITY_COLLEGE_COORDINATOR,
                'source_department_id' => null,
            ]);

            $this->addStaffMemberToCommittee(
                $committee,
                $sameDeptStaff,
                CommitteeMember::ROLE_SAME_DEPARTMENT_MEMBER,
                $department->id,
            );

            $otherMemberRole = (int) $otherDeptStaff->department_id === (int) $department->id
                ? CommitteeMember::ROLE_SAME_DEPARTMENT_MEMBER
                : CommitteeMember::ROLE_OTHER_DEPARTMENT_MEMBER;
            $this->addStaffMemberToCommittee(
                $committee,
                $otherDeptStaff,
                $otherMemberRole,
                $otherDeptStaff->department_id,
            );

            $this->seedHdEvaluations($committee);
            app(SuperAdminEvaluationAssignmentService::class)->syncForCommittee($committee);
            $this->grantCommitteeRoles($committee, RolePermissionSeeder::ROLE_HD_COMMITTEE);

            return $committee->load('members.staffMember', 'members.user');
        });
    }

    /**
     * Ensure each evaluator user has the given non-elevating role so they can
     * see and complete their evaluations. The coordinator keeps their own role.
     */
    private function grantCommitteeRoles(Committee $committee, string $roleName): void
    {
        $members = $committee->members()->whereNotNull('user_id')->get();
        foreach ($members as $m) {
            if ($m->member_role === CommitteeMember::ROLE_QUALITY_COLLEGE_COORDINATOR) {
                continue;
            }
            $user = User::find($m->user_id);
            if ($user && ! $user->isSuperAdmin()) {
                app(UserAccessSyncService::class)->sync($user);
            }
        }
    }

    /**
     * For each member-evaluator and each teaching staff of the department,
     * pre-create a draft evaluation row so the workload is visible.
     */
    private function seedLocalEvaluations(Committee $committee): void
    {
        $this->seedEvaluations(
            $committee,
            LocalCommitteeEvaluateeResolver::forDepartment((int) $committee->department_id),
        );
    }

    /**
     * For HD committee: the only evaluatee is the department head.
     */
    private function seedHdEvaluations(Committee $committee): void
    {
        $department = Department::find($committee->department_id);
        if (! $department || ! $department->head_staff_id) {
            return;
        }
        $head = StaffMember::find($department->head_staff_id);
        if (! $head) {
            return;
        }

        $this->seedEvaluations($committee, collect([$head]));
    }

    /**
     * @param Collection<int, StaffMember> $staffList
     */
    private function seedEvaluations(Committee $committee, Collection $staffList): void
    {
        if (! $committee->evaluation_form_id) {
            return;
        }

        $evaluators = $committee->members()->whereNotNull('user_id')->get();

        foreach ($evaluators as $member) {
            foreach ($staffList as $staff) {
                if ($staff->id === $member->staff_member_id) {
                    continue;
                }
                Evaluation::firstOrRestoreOrCreate(
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
            }
        }
    }

    private function assertValidOtherDepartmentMember(StaffMember $otherDeptStaff, Department $department): void
    {
        if ((int) $otherDeptStaff->department_id === (int) $department->id) {
            throw new RuntimeException(__('committees.other_member_different_department'));
        }
    }

    private function assertValidHdExternalMember(StaffMember $staff, Department $department): void
    {
        if ((int) $staff->id === (int) $department->head_staff_id) {
            throw new RuntimeException(__('messages.committee_head_is_evaluatee'));
        }
    }

    private function addStaffMemberToCommittee(
        Committee $committee,
        ?StaffMember $staff,
        string $memberRole,
        ?int $sourceDepartmentId = null,
    ): void {
        if (! $staff) {
            throw new RuntimeException(__('messages.committee_staff_missing'));
        }

        $user = $this->ensureUserForStaff($staff);
        if (! $user) {
            throw new RuntimeException(__('messages.committee_user_create_failed', ['name' => LocaleHelper::staffDisplayName($staff)]));
        }

        CommitteeMember::create([
            'committee_id'         => $committee->id,
            'user_id'              => $user->id,
            'staff_member_id'      => $staff->id,
            'member_role'          => $memberRole,
            'source_department_id' => $sourceDepartmentId,
        ]);
    }

    /**
     * Create or refresh the User account associated with a staff member so
     * they can sign in via Google. Roles are also synced.
     */
    public function ensureUserForStaff(?StaffMember $staff): ?User
    {
        if (! $staff) {
            return null;
        }

        $user = null;
        if ($staff->user_id) {
            $user = User::withTrashed()->find($staff->user_id);
        }
        if (! $user) {
            $user = User::query()
                ->where('email', mb_strtolower(trim($staff->email)))
                ->whereNull('deleted_at')
                ->first();
        }
        if ($user?->trashed()) {
            $user->restore();
        }

        if (! $user) {
            app(\App\Services\Staff\StaffUserEmailService::class)
                ->releaseEmailForStaffAccount(mb_strtolower(trim($staff->email)), $staff);

            $user = User::create([
                'name'      => $staff->full_name_en,
                'email'     => $staff->email,
                'password'  => Hash::make(Str::random(40)),
                'is_active' => true,
            ]);
        } else {
            $this->syncLinkedUserFromStaff($user, $staff);
        }

        if ($user->staff_member_id !== $staff->id) {
            $user->staff_member_id = $staff->id;
            $user->save();
        }
        if ($staff->user_id !== $user->id) {
            $staff->user_id = $user->id;
            $staff->save();
        }

        app(UserAccessSyncService::class)->sync($user);

        return $user;
    }

    private function syncLinkedUserFromStaff(User $user, StaffMember $staff): void
    {
        $email = mb_strtolower(trim($staff->email));

        if (mb_strtolower($user->email) !== $email) {
            app(\App\Services\Staff\StaffUserEmailService::class)
                ->reclaimEmailForLinkedUser($user, $staff, $email);

            $user->email = $email;
        }

        if ($user->name !== $staff->full_name_en) {
            $user->name = $staff->full_name_en;
        }

        $user->save();
    }
}
