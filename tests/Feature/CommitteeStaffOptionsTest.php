<?php

use App\Models\College;
use App\Models\Department;
use App\Models\StaffMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $this->college = College::first();
    $this->department = $this->college->departments()->first();
    $this->otherDeptInCollege = $this->college->departments()->where('id', '!=', $this->department->id)->first();

    $this->otherCollege = College::query()->where('id', '!=', $this->college->id)->firstOrFail();
    $this->otherCollegeDept = $this->otherCollege->departments()->first();

    $this->deptHead = StaffMember::create([
        'full_name_en' => 'Dept Head',
        'email' => 'head.multi@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->department->id,
        'is_active' => true,
    ]);
    $this->department->update(['head_staff_id' => $this->deptHead->id]);

    $this->sameDeptStaff = StaffMember::create([
        'full_name_en' => 'Same Dept Staff',
        'email' => 'samedept@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->department->id,
        'is_active' => true,
    ]);

    $this->otherCollegeStaff = StaffMember::create([
        'full_name_en' => 'Other College Staff',
        'email' => 'othercollege@uoz.edu.krd',
        'college_id' => $this->otherCollege->id,
        'department_id' => $this->otherCollegeDept->id,
        'is_active' => true,
    ]);

    $this->coordinator = User::create([
        'name' => 'Coord',
        'email' => 'coord-multi@uoz.edu.krd',
        'password' => Hash::make(Str::random(40)),
        'college_id' => $this->college->id,
        'is_active' => true,
    ]);
    $this->coordinator->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);
});

it('returns university-wide staff for local external member regardless of department count', function () {
    $response = $this->actingAs($this->coordinator)->getJson(route('committees.staff-options', [
        'college_id' => $this->college->id,
        'filter' => 'other',
        'exclude_department_id' => $this->department->id,
    ]));

    $response->assertOk()
        ->assertJsonPath('university_wide', true);

    $ids = collect($response->json('items'))->pluck('id');

    expect($ids)->toContain($this->otherCollegeStaff->id)
        ->and($ids)->not->toContain($this->sameDeptStaff->id);
});

it('returns university-wide staff for HD external member regardless of department count', function () {
    $response = $this->actingAs($this->coordinator)->getJson(route('committees.staff-options', [
        'college_id' => $this->college->id,
        'filter' => 'college',
        'department_id' => $this->department->id,
        'exclude_head' => '1',
    ]));

    $response->assertOk()
        ->assertJsonPath('university_wide', true);

    expect(collect($response->json('items'))->pluck('id'))
        ->toContain($this->otherCollegeStaff->id);
});

it('allows local external member from any college when departments differ', function () {
    $service = app(\App\Services\Committees\CommitteeService::class);
    $period = \App\Models\EvaluationPeriod::first();

    $committee = $service->createLocalCommittee($this->coordinator, [
        'department_id' => $this->department->id,
        'same_department_member_id' => $this->sameDeptStaff->id,
        'other_department_member_id' => $this->otherCollegeStaff->id,
        'evaluation_period_id' => $period->id,
    ]);

    expect($committee)->toBeInstanceOf(\App\Models\Committee::class);
});

it('allows HD external member from any college', function () {
    $service = app(\App\Services\Committees\CommitteeService::class);
    $period = \App\Models\EvaluationPeriod::first();

    $dean = StaffMember::create([
        'full_name_en' => 'Dean',
        'email' => 'dean.multi@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->department->id,
        'is_active' => true,
    ]);
    $this->college->update(['dean_staff_id' => $dean->id]);

    $committee = $service->createHdCommittee($this->coordinator, [
        'department_id' => $this->department->id,
        'same_department_member_id' => $this->sameDeptStaff->id,
        'other_department_member_id' => $this->otherCollegeStaff->id,
        'evaluation_period_id' => $period->id,
    ]);

    expect($committee)->toBeInstanceOf(\App\Models\Committee::class);
});

it('still scopes same-department member picker to the selected department', function () {
    $response = $this->actingAs($this->coordinator)->getJson(route('committees.staff-options', [
        'college_id' => $this->college->id,
        'filter' => 'department',
        'department_id' => $this->department->id,
        'exclude_head' => '1',
    ]));

    $response->assertOk()
        ->assertJsonPath('university_wide', false);

    $ids = collect($response->json('items'))->pluck('id');

    expect($ids)->toContain($this->sameDeptStaff->id)
        ->and($ids)->not->toContain($this->otherCollegeStaff->id);
});
