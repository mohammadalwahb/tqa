<?php

use App\Models\College;
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

    $this->period = EvaluationPeriod::first();

    $this->collegeA = College::first();
    $this->collegeB = College::create([
        'name_en'   => 'Other College',
        'name_ku'   => 'کۆلێژی تر',
        'is_active' => true,
    ]);

    $this->deptA = $this->collegeA->departments()->first();
    $this->deptB = Department::create([
        'college_id' => $this->collegeB->id,
        'name_en'    => 'Dept B',
        'name_ku'    => 'بەشی ب',
        'is_active'  => true,
    ]);

    $this->coordinatorA = User::create([
        'name'       => 'Coord A',
        'email'      => 'coord-a-report@uoz.edu.krd',
        'password'   => Hash::make(Str::random(40)),
        'college_id' => $this->collegeA->id,
        'is_active'  => true,
    ]);
    $this->coordinatorA->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);

    $this->headA = StaffMember::create([
        'full_name_en'      => 'Head A',
        'email'             => 'head-a-report@uoz.edu.krd',
        'college_id'        => $this->collegeA->id,
        'department_id'     => $this->deptA->id,
        'is_active'         => true,
        'is_teaching_staff' => false,
    ]);
    $this->deptA->update(['head_staff_id' => $this->headA->id]);

    $this->teacherA = StaffMember::create([
        'full_name_en'      => 'Teacher A',
        'email'             => 'teacher-a-report@uoz.edu.krd',
        'college_id'        => $this->collegeA->id,
        'department_id'     => $this->deptA->id,
        'is_active'         => true,
        'is_teaching_staff' => true,
    ]);

    $this->sameA = StaffMember::create([
        'full_name_en'      => 'Same A',
        'email'             => 'same-a-report@uoz.edu.krd',
        'college_id'        => $this->collegeA->id,
        'department_id'     => $this->deptA->id,
        'is_active'         => true,
        'is_teaching_staff' => true,
    ]);

    $this->otherB = StaffMember::create([
        'full_name_en'      => 'Other B',
        'email'             => 'other-b-report@uoz.edu.krd',
        'college_id'        => $this->collegeB->id,
        'department_id'     => $this->deptB->id,
        'is_active'         => true,
        'is_teaching_staff' => true,
    ]);

    $this->headB = StaffMember::create([
        'full_name_en'      => 'Head B',
        'email'             => 'head-b-report@uoz.edu.krd',
        'college_id'        => $this->collegeB->id,
        'department_id'     => $this->deptB->id,
        'is_active'         => true,
        'is_teaching_staff' => false,
    ]);
    $this->deptB->update(['head_staff_id' => $this->headB->id]);

    $this->teacherB = StaffMember::create([
        'full_name_en'      => 'Teacher B',
        'email'             => 'teacher-b-report@uoz.edu.krd',
        'college_id'        => $this->collegeB->id,
        'department_id'     => $this->deptB->id,
        'is_active'         => true,
        'is_teaching_staff' => true,
    ]);

    $this->sameB = StaffMember::create([
        'full_name_en'      => 'Same B',
        'email'             => 'same-b-report@uoz.edu.krd',
        'college_id'        => $this->collegeB->id,
        'department_id'     => $this->deptB->id,
        'is_active'         => true,
        'is_teaching_staff' => true,
    ]);

    $service = app(CommitteeService::class);

    $service->createLocalCommittee($this->coordinatorA, [
        'department_id'              => $this->deptA->id,
        'same_department_member_id' => $this->sameA->id,
        'other_department_member_id' => $this->otherB->id,
        'evaluation_period_id'       => $this->period->id,
    ]);

    $coordinatorB = User::create([
        'name'       => 'Coord B',
        'email'      => 'coord-b-report@uoz.edu.krd',
        'password'   => Hash::make(Str::random(40)),
        'college_id' => $this->collegeB->id,
        'is_active'  => true,
    ]);
    $coordinatorB->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);

    $service->createLocalCommittee($coordinatorB, [
        'department_id'              => $this->deptB->id,
        'same_department_member_id' => $this->sameB->id,
        'other_department_member_id' => $this->sameA->id,
        'evaluation_period_id'       => $this->period->id,
    ]);
});

it('scopes coordinator reports to their college staff only', function () {
    $response = $this->actingAs($this->coordinatorA)
        ->get(route('reports.index', ['period_id' => $this->period->id]))
        ->assertOk();

    $response->assertSee($this->teacherA->full_name_en);
    $response->assertSee($this->sameA->full_name_en);
    $response->assertDontSee($this->teacherB->full_name_en);
    $response->assertDontSee($this->sameB->full_name_en);
});

it('forbids coordinator from viewing another college staff report details', function () {
    $this->actingAs($this->coordinatorA)
        ->get(route('reports.staff.details', [
            'staff'     => $this->teacherB->id,
            'period_id' => $this->period->id,
        ]))
        ->assertForbidden();
});

it('shows all colleges for super admin reports', function () {
    $admin = User::role('Super Admin')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('reports.index', ['period_id' => $this->period->id]))
        ->assertOk()
        ->assertSee($this->teacherA->full_name_en)
        ->assertSee($this->teacherB->full_name_en);
});
