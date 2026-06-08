<?php

use App\Models\College;
use App\Models\CommitteeMember;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\Committees\CommitteeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $this->college = College::first();
    $this->deptA   = $this->college->departments()->first();
    $this->deptB   = $this->college->departments()->where('id', '!=', $this->deptA->id)->first();
    $this->period  = EvaluationPeriod::first();

    $this->coordinator = User::create([
        'name'       => 'Coord One',
        'email'      => 'coord-fill@uoz.edu.krd',
        'password'   => Hash::make(Str::random(40)),
        'college_id' => $this->college->id,
        'is_active'  => true,
    ]);
    $this->coordinator->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);

    $this->deptHead = StaffMember::create([
        'full_name_en'      => 'Department Head',
        'email'             => 'head-fill@uoz.edu.krd',
        'college_id'        => $this->college->id,
        'department_id'     => $this->deptA->id,
        'is_active'         => true,
        'is_teaching_staff' => false,
    ]);
    $this->deptA->update(['head_staff_id' => $this->deptHead->id]);

    $this->staffSame1 = StaffMember::create([
        'full_name_en'      => 'Same Dept Member 1',
        'email'             => 'same-fill@uoz.edu.krd',
        'college_id'        => $this->college->id,
        'department_id'     => $this->deptA->id,
        'is_active'         => true,
        'is_teaching_staff' => true,
    ]);
    $this->staffSame2 = StaffMember::create([
        'full_name_en'      => 'Same Dept Member 2',
        'email'             => 'same2-fill@uoz.edu.krd',
        'college_id'        => $this->college->id,
        'department_id'     => $this->deptA->id,
        'is_active'         => true,
        'is_teaching_staff' => true,
    ]);
    $this->staffOther = StaffMember::create([
        'full_name_en'      => 'Other Dept Member',
        'email'             => 'other-fill@uoz.edu.krd',
        'college_id'        => $this->college->id,
        'department_id'     => $this->deptB->id,
        'is_active'         => true,
        'is_teaching_staff' => true,
    ]);
});

it('shows fill only for evaluations assigned to the logged-in user', function () {
    $committee = app(CommitteeService::class)->createLocalCommittee($this->coordinator, [
        'department_id'              => $this->deptA->id,
        'same_department_member_id' => $this->staffSame1->id,
        'other_department_member_id' => $this->staffOther->id,
        'evaluation_period_id'       => $this->period->id,
    ]);

    $coordinatorEvaluation = Evaluation::query()
        ->where('committee_id', $committee->id)
        ->where('evaluator_user_id', $this->coordinator->id)
        ->where('status', Evaluation::STATUS_DRAFT)
        ->first();

    $memberEvaluation = Evaluation::query()
        ->where('committee_id', $committee->id)
        ->where('evaluator_user_id', '!=', $this->coordinator->id)
        ->where('status', '!=', Evaluation::STATUS_SUBMITTED)
        ->first();

    expect($coordinatorEvaluation)->not->toBeNull()
        ->and($memberEvaluation)->not->toBeNull();

    $coordinatorResponse = $this->actingAs($this->coordinator)
        ->get(route('evaluations.index'))
        ->assertOk();

    $coordinatorResponse->assertSee(route('evaluations.edit', $coordinatorEvaluation), false);
    $coordinatorResponse->assertDontSee(route('evaluations.edit', $memberEvaluation), false);

    $memberUser = User::find(
        CommitteeMember::query()
            ->where('committee_id', $committee->id)
            ->where('member_role', CommitteeMember::ROLE_SAME_DEPARTMENT_MEMBER)
            ->value('user_id')
    );

    expect($memberUser)->not->toBeNull();

    $memberOwnEvaluation = Evaluation::query()
        ->where('committee_id', $committee->id)
        ->where('evaluator_user_id', $memberUser->id)
        ->where('status', '!=', Evaluation::STATUS_SUBMITTED)
        ->first();

    expect($memberOwnEvaluation)->not->toBeNull();

    $this->actingAs($memberUser)
        ->get(route('evaluations.index'))
        ->assertOk()
        ->assertSee(route('evaluations.edit', $memberOwnEvaluation), false)
        ->assertDontSee(route('evaluations.edit', $coordinatorEvaluation), false);
});
