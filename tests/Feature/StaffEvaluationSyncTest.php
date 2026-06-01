<?php

use App\Models\Committee;
use App\Models\Evaluation;
use App\Models\StaffMember;
use App\Services\Committees\CommitteeEvaluationSyncService;
use App\Services\Committees\CommitteeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
    $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class]);

    $this->service = app(CommitteeService::class);
    $this->sync = app(CommitteeEvaluationSyncService::class);

    $this->college = \App\Models\College::first();
    $this->deptA = $this->college->departments()->first();
    $this->deptB = $this->college->departments()->where('id', '!=', $this->deptA->id)->first();
    $this->period = \App\Models\EvaluationPeriod::first();

    $this->coordinator = \App\Models\User::create([
        'name' => 'Coord',
        'email' => 'coord-sync@uoz.edu.krd',
        'password' => bcrypt('secret'),
        'college_id' => $this->college->id,
        'is_active' => true,
    ]);
    $this->coordinator->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);

    $this->deptHead = StaffMember::create([
        'full_name_en' => 'Head',
        'email' => 'head-sync@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptA->id,
        'is_active' => true,
    ]);
    $this->deptA->update(['head_staff_id' => $this->deptHead->id]);

    $this->teaching = StaffMember::create([
        'full_name_en' => 'Teacher',
        'email' => 'teacher-sync@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptA->id,
        'is_active' => true,
        'is_teaching_staff' => true,
    ]);

    $this->other = StaffMember::create([
        'full_name_en' => 'Other',
        'email' => 'other-sync@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptB->id,
        'is_active' => true,
        'is_teaching_staff' => true,
    ]);

    $this->same = StaffMember::create([
        'full_name_en' => 'Same',
        'email' => 'same-sync@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptA->id,
        'is_active' => true,
        'is_teaching_staff' => true,
    ]);
});

it('creates evaluations when teaching staff is added to a department with a local committee', function () {
    $committee = $this->service->createLocalCommittee($this->coordinator, [
        'department_id' => $this->deptA->id,
        'same_department_member_id' => $this->same->id,
        'other_department_member_id' => $this->other->id,
        'evaluation_period_id' => $this->period->id,
    ]);

    $newTeacher = StaffMember::create([
        'full_name_en' => 'New Teacher',
        'email' => 'newteacher@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptA->id,
        'is_active' => true,
        'is_teaching_staff' => true,
    ]);

    $created = $this->sync->syncTeachingStaffToLocalCommittees($newTeacher);

    expect($created)->toBeGreaterThan(0)
        ->and(Evaluation::where('committee_id', $committee->id)
            ->where('evaluatee_staff_id', $newTeacher->id)
            ->count())->toBeGreaterThan(0);
});

it('removes evaluations when teaching staff is deactivated', function () {
    $committee = $this->service->createLocalCommittee($this->coordinator, [
        'department_id' => $this->deptA->id,
        'same_department_member_id' => $this->same->id,
        'other_department_member_id' => $this->other->id,
        'evaluation_period_id' => $this->period->id,
    ]);

    $this->sync->syncTeachingStaffToLocalCommittees($this->teaching);

    expect(Evaluation::where('committee_id', $committee->id)
        ->where('evaluatee_staff_id', $this->teaching->id)
        ->count())->toBeGreaterThan(0);

    $this->teaching->update(['is_teaching_staff' => false]);

    $removed = $this->sync->reconcileLocalEvaluationsForStaff($this->teaching->fresh());

    expect($removed['removed'])->toBeGreaterThan(0)
        ->and(Evaluation::where('committee_id', $committee->id)
            ->where('evaluatee_staff_id', $this->teaching->id)
            ->count())->toBe(0);
});

it('does not create local evaluations for the department head', function () {
    $committee = $this->service->createLocalCommittee($this->coordinator, [
        'department_id' => $this->deptA->id,
        'same_department_member_id' => $this->same->id,
        'other_department_member_id' => $this->other->id,
        'evaluation_period_id' => $this->period->id,
    ]);

    $created = $this->sync->syncTeachingStaffToLocalCommittees($this->deptHead);

    expect($created)->toBe(0)
        ->and(Evaluation::where('committee_id', $committee->id)
            ->where('evaluatee_staff_id', $this->deptHead->id)
            ->exists())->toBeFalse();
});
