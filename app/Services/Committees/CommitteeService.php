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
     * - Member #1: the Quality College Coordinator (user) - automatically.
     * - Member #2: one staff member from the SAME department.
     * - Member #3: one staff member from ANOTHER department of the same college.
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
        $department = Department::with('college')->findOrFail($data['department_id']);
        $period     = EvaluationPeriod::findOrFail($data['evaluation_period_id']);
        $form       = isset($data['evaluation_form_id']) && $data['evaluation_form_id']
            ? EvaluationForm::findOrFail($data['evaluation_form_id'])
            : EvaluationForm::where('target_type', 'staff')->where('is_active', true)->first();

        if (! $coordinator->college_id || (int) $coordinator->college_id !== (int) $department->college_id) {
            throw new RuntimeException('You can only create committees for your own college.');
        }

        $sameDeptStaffId  = (int) $data['same_department_member_id'];
        $otherDeptStaffId = (int) $data['other_department_member_id'];

        if ($sameDeptStaffId === $otherDeptStaffId) {
            throw new RuntimeException('Same-department and other-department members must be different people.');
        }

        $sameDeptStaff = StaffMember::findOrFail($sameDeptStaffId);
        if ((int) $sameDeptStaff->department_id !== (int) $department->id) {
            throw new RuntimeException('The same-department member must belong to the chosen department.');
        }

        $otherDeptStaff = StaffMember::findOrFail($otherDeptStaffId);
        if ($otherDeptStaff->department_id === $department->id) {
            throw new RuntimeException('The other-department member must be from a different department.');
        }
        if ($otherDeptStaff->college_id !== $department->college_id) {
            throw new RuntimeException('The fourth member must be from another department of the same college.');
        }

        return DB::transaction(function () use ($coordinator, $department, $period, $form, $sameDeptStaff, $otherDeptStaff, $data) {
            $exists = Committee::where('type', Committee::TYPE_LOCAL)
                ->where('department_id', $department->id)
                ->where('evaluation_period_id', $period->id)
                ->whereNull('deleted_at')
                ->first();

            if ($exists) {
                throw new RuntimeException('A local committee already exists for this department in this period.');
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
     * - One other-department member from the same college (may be from the same department)
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
            throw new RuntimeException("This department has no Head of Department assigned. Cannot build an HD committee.");
        }
        if (! $college?->dean_staff_id) {
            throw new RuntimeException('The Dean of the College must be assigned before creating an HD committee.');
        }

        if (! $coordinator->college_id || (int) $coordinator->college_id !== (int) $college->id) {
            throw new RuntimeException('You can only create committees for your own college.');
        }

        $sameDeptStaffId  = (int) $data['same_department_member_id'];
        $otherDeptStaffId = (int) $data['other_department_member_id'];

        if ($sameDeptStaffId === $otherDeptStaffId) {
            throw new RuntimeException('Same-department and other-department members must be different people.');
        }

        $sameDeptStaff = StaffMember::findOrFail($sameDeptStaffId);
        if ((int) $sameDeptStaff->department_id !== (int) $department->id) {
            throw new RuntimeException('The same-department member must belong to the chosen department.');
        }
        if ((int) $sameDeptStaff->id === (int) $department->head_staff_id) {
            throw new RuntimeException('The department head cannot be a committee member; they are the evaluatee.');
        }

        $otherDeptStaff = StaffMember::findOrFail($otherDeptStaffId);
        if ((int) $otherDeptStaff->college_id !== (int) $college->id) {
            throw new RuntimeException('The other-department member must belong to the same college.');
        }
        if ((int) $otherDeptStaff->id === (int) $department->head_staff_id) {
            throw new RuntimeException('The department head cannot be a committee member; they are the evaluatee.');
        }

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
                throw new RuntimeException('An HD committee already exists for this department in this period.');
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
            if ($user && ! $user->hasRole($roleName) && ! $user->isSuperAdmin()) {
                $user->assignRole($roleName);
            }
        }
    }

    /**
     * For each member-evaluator and each teaching staff of the department,
     * pre-create a draft evaluation row so the workload is visible.
     */
    private function seedLocalEvaluations(Committee $committee): void
    {
        $staffToEvaluate = StaffMember::where('department_id', $committee->department_id)
            ->where('is_active', true)
            ->where('is_teaching_staff', true)
            ->get();

        $this->seedEvaluations($committee, $staffToEvaluate);
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
                Evaluation::firstOrCreate(
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

    private function addStaffMemberToCommittee(
        Committee $committee,
        ?StaffMember $staff,
        string $memberRole,
        ?int $sourceDepartmentId = null,
    ): void {
        if (! $staff) {
            throw new RuntimeException('A committee staff member record is missing.');
        }

        $user = $this->ensureUserForStaff($staff);
        if (! $user) {
            throw new RuntimeException("Could not create a user account for {$staff->full_name_en}.");
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

        $user = $staff->user_id
            ? User::find($staff->user_id)
            : User::where('email', $staff->email)->first();

        if (! $user) {
            $user = User::create([
                'name'      => $staff->full_name_en,
                'email'     => $staff->email,
                'password'  => Hash::make(Str::random(40)),
                'is_active' => true,
            ]);
        }

        if ($user->staff_member_id !== $staff->id) {
            $user->staff_member_id = $staff->id;
            $user->save();
        }
        if ($staff->user_id !== $user->id) {
            $staff->user_id = $user->id;
            $staff->save();
        }

        return $user;
    }
}
