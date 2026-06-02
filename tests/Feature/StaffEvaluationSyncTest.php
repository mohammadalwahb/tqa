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

it('syncs linked user email when staff email is updated before committee creation', function () {
    $this->deptHead->update(['email' => 'head-new@uoz.edu.krd']);
    app(\App\Services\Committees\CommitteeService::class)->ensureUserForStaff($this->deptHead->fresh());

    expect($this->deptHead->fresh()->user?->email)->toBe('head-new@uoz.edu.krd');

    $committee = $this->service->createLocalCommittee($this->coordinator, [
        'department_id' => $this->deptA->id,
        'same_department_member_id' => $this->same->id,
        'other_department_member_id' => $this->other->id,
        'evaluation_period_id' => $this->period->id,
    ]);

    $headMember = $committee->members->firstWhere(
        'member_role',
        \App\Models\CommitteeMember::ROLE_HEAD_OF_DEPARTMENT
    );

    expect($headMember)->not->toBeNull()
        ->and($headMember->displayEmail())->toBe('head-new@uoz.edu.krd');
});

it('preserves existing evaluations when staff details are updated', function () {
    $committee = $this->service->createLocalCommittee($this->coordinator, [
        'department_id' => $this->deptA->id,
        'same_department_member_id' => $this->same->id,
        'other_department_member_id' => $this->other->id,
        'evaluation_period_id' => $this->period->id,
    ]);

    $this->sync->syncTeachingStaffToLocalCommittees($this->teaching);

    $before = Evaluation::where('committee_id', $committee->id)
        ->where('evaluatee_staff_id', $this->teaching->id)
        ->pluck('id')
        ->sort()
        ->values();

    expect($before)->not->toBeEmpty();

    $this->teaching->update(['full_name_en' => 'Teacher Updated', 'email' => 'teacher-updated@uoz.edu.krd']);
    app(\App\Services\Committees\CommitteeService::class)->ensureUserForStaff($this->teaching->fresh());
    $this->sync->reconcileLocalEvaluationsForStaff($this->teaching->fresh());

    $after = Evaluation::where('committee_id', $committee->id)
        ->where('evaluatee_staff_id', $this->teaching->id)
        ->pluck('id')
        ->sort()
        ->values();

    expect($after)->toEqual($before);
});

it('restores soft-deleted evaluations when teaching staff is reactivated', function () {
    $committee = $this->service->createLocalCommittee($this->coordinator, [
        'department_id' => $this->deptA->id,
        'same_department_member_id' => $this->same->id,
        'other_department_member_id' => $this->other->id,
        'evaluation_period_id' => $this->period->id,
    ]);

    $this->sync->syncTeachingStaffToLocalCommittees($this->teaching);
    $evaluationId = Evaluation::where('evaluatee_staff_id', $this->teaching->id)->value('id');

    $this->teaching->update(['is_teaching_staff' => false]);
    $this->sync->reconcileLocalEvaluationsForStaff($this->teaching->fresh());

    expect(Evaluation::find($evaluationId))->toBeNull()
        ->and(Evaluation::withTrashed()->find($evaluationId)?->trashed())->toBeTrue();

    $this->teaching->update(['is_teaching_staff' => true]);
    $this->sync->reconcileLocalEvaluationsForStaff($this->teaching->fresh());

    expect(Evaluation::find($evaluationId))->not->toBeNull()
        ->and(Evaluation::find($evaluationId)?->trashed())->toBeFalse();
});

it('preserves existing evaluations when staff profile is updated', function () {
    $committee = $this->service->createLocalCommittee($this->coordinator, [
        'department_id' => $this->deptA->id,
        'same_department_member_id' => $this->same->id,
        'other_department_member_id' => $this->other->id,
        'evaluation_period_id' => $this->period->id,
    ]);

    $this->sync->syncTeachingStaffToLocalCommittees($this->teaching);

    $evaluationIds = Evaluation::query()
        ->where('evaluatee_staff_id', $this->teaching->id)
        ->where('committee_id', $committee->id)
        ->pluck('id');

    expect($evaluationIds)->not->toBeEmpty();

    $admin = \App\Models\User::role('Super Admin')->firstOrFail();
    $employeeType = \App\Models\StaffLookupOption::query()
        ->forField(\App\Enums\StaffLookupField::EmployeeType)
        ->active()
        ->value('name');

    $this->actingAs($admin)->put(route('staff.update', $this->teaching), [
        'full_name_en' => 'Teacher Updated',
        'email' => $this->teaching->email,
        'college_id' => $this->teaching->college_id,
        'department_id' => $this->teaching->department_id,
        'employee_type' => $employeeType,
        'is_teaching_staff' => true,
        'is_active' => true,
    ])->assertRedirect(route('staff.index'));

    expect(Evaluation::query()
        ->whereIn('id', $evaluationIds)
        ->count())->toBe($evaluationIds->count())
        ->and(Evaluation::onlyTrashed()
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
