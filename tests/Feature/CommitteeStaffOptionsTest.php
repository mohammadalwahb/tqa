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

    $this->singleDeptCollege = College::create([
        'name_en' => 'Single Dept College',
        'name_ku' => 'کۆلێژی یەک بەش',
        'is_active' => true,
    ]);

    $this->onlyDept = Department::create([
        'college_id' => $this->singleDeptCollege->id,
        'name_en' => 'Only Department',
        'name_ku' => 'تەنها بەش',
        'is_active' => true,
    ]);

    $this->otherCollege = College::first();
    $this->otherDept = $this->otherCollege->departments()->first();

    $this->deptHead = StaffMember::create([
        'full_name_en' => 'Only Dept Head',
        'email' => 'head.only@uoz.edu.krd',
        'college_id' => $this->singleDeptCollege->id,
        'department_id' => $this->onlyDept->id,
        'is_active' => true,
    ]);
    $this->onlyDept->update(['head_staff_id' => $this->deptHead->id]);

    $this->staffInOnlyDept = StaffMember::create([
        'full_name_en' => 'Only Dept Staff',
        'email' => 'onlydept@uoz.edu.krd',
        'college_id' => $this->singleDeptCollege->id,
        'department_id' => $this->onlyDept->id,
        'is_active' => true,
    ]);

    $this->staffElsewhere = StaffMember::create([
        'full_name_en' => 'Other College Staff',
        'email' => 'elsewhere@uoz.edu.krd',
        'college_id' => $this->otherCollege->id,
        'department_id' => $this->otherDept->id,
        'is_active' => true,
    ]);

    $this->coordinator = User::create([
        'name' => 'Coord',
        'email' => 'coord-single@uoz.edu.krd',
        'password' => Hash::make(Str::random(40)),
        'college_id' => $this->singleDeptCollege->id,
        'is_active' => true,
    ]);
    $this->coordinator->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);
});

it('returns university-wide staff when college has one department and other member is requested', function () {
    $response = $this->actingAs($this->coordinator)->getJson(route('committees.staff-options', [
        'college_id' => $this->singleDeptCollege->id,
        'filter' => 'other',
        'exclude_department_id' => $this->onlyDept->id,
    ]));

    $response->assertOk()
        ->assertJsonPath('university_wide', true);

    $ids = collect($response->json('items'))->pluck('id');

    expect($ids)->toContain($this->staffElsewhere->id)
        ->and($ids)->not->toContain($this->staffInOnlyDept->id);
});

it('returns university-wide staff for HD college member when college has one department', function () {
    $response = $this->actingAs($this->coordinator)->getJson(route('committees.staff-options', [
        'college_id' => $this->singleDeptCollege->id,
        'filter' => 'college',
        'department_id' => $this->onlyDept->id,
        'exclude_head' => '1',
    ]));

    $response->assertOk()
        ->assertJsonPath('university_wide', true);

    $ids = collect($response->json('items'))->pluck('id');

    expect($ids)->toContain($this->staffElsewhere->id);
});

it('allows HD external member from any college for single-department college', function () {
    $service = app(\App\Services\Committees\CommitteeService::class);
    $period = \App\Models\EvaluationPeriod::first();

    $dean = StaffMember::create([
        'full_name_en' => 'Dean',
        'email' => 'dean.single@uoz.edu.krd',
        'college_id' => $this->singleDeptCollege->id,
        'department_id' => $this->onlyDept->id,
        'is_active' => true,
    ]);
    $this->singleDeptCollege->update(['dean_staff_id' => $dean->id]);

    $committee = $service->createHdCommittee($this->coordinator, [
        'department_id' => $this->onlyDept->id,
        'same_department_member_id' => $this->staffInOnlyDept->id,
        'other_department_member_id' => $this->staffElsewhere->id,
        'evaluation_period_id' => $period->id,
    ]);

    expect($committee)->toBeInstanceOf(\App\Models\Committee::class);
});

it('allows other member from any college when validating single-department college', function () {
    $service = app(\App\Services\Committees\CommitteeService::class);
    $period = \App\Models\EvaluationPeriod::first();

    $committee = $service->createLocalCommittee($this->coordinator, [
        'department_id' => $this->onlyDept->id,
        'same_department_member_id' => $this->staffInOnlyDept->id,
        'other_department_member_id' => $this->staffElsewhere->id,
        'evaluation_period_id' => $period->id,
    ]);

    expect($committee)->toBeInstanceOf(\App\Models\Committee::class);
});
