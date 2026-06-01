<?php

use App\Enums\StaffLookupField;
use App\Models\Committee;
use App\Models\Evaluation;
use App\Models\StaffLookupOption;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\Users\UserAccessSyncService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class]);

    $this->employeeType = StaffLookupOption::query()
        ->forField(StaffLookupField::EmployeeType)
        ->active()
        ->value('name');

    $this->college = \App\Models\College::query()->firstOrFail();
    $this->department = $this->college->departments()->firstOrFail();
    $this->period = \App\Models\EvaluationPeriod::firstOrFail();

    $this->headStaff = StaffMember::create([
        'full_name_en' => 'Dept Head',
        'email' => 'dept.head@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->department->id,
        'is_active' => true,
        'is_teaching_staff' => false,
    ]);
    $this->department->update(['head_staff_id' => $this->headStaff->id]);

    $this->hod = User::create([
        'name' => 'Dept Head',
        'email' => 'dept.head@uoz.edu.krd',
        'password' => bcrypt('secret'),
        'staff_member_id' => $this->headStaff->id,
        'is_active' => true,
    ]);
    $this->headStaff->update(['user_id' => $this->hod->id]);
    app(UserAccessSyncService::class)->sync($this->hod->fresh());

    $this->coordinator = User::create([
        'name' => 'Coordinator',
        'email' => 'coord-hod@uoz.edu.krd',
        'password' => bcrypt('secret'),
        'college_id' => $this->college->id,
        'is_active' => true,
    ]);
    $this->coordinator->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);

    $this->otherDept = $this->college->departments()->where('id', '!=', $this->department->id)->first();
    if (! $this->otherDept) {
        $this->otherDept = \App\Models\Department::create([
            'college_id' => $this->college->id,
            'name_en' => 'Other Dept',
            'is_active' => true,
        ]);
    }

    $this->peer = StaffMember::create([
        'full_name_en' => 'Peer Teacher',
        'email' => 'peer.teacher@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->department->id,
        'is_active' => true,
        'is_teaching_staff' => true,
        'employee_type' => $this->employeeType,
    ]);

    $this->outsider = StaffMember::create([
        'full_name_en' => 'Outsider',
        'email' => 'outsider@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->otherDept->id,
        'is_active' => true,
        'is_teaching_staff' => true,
        'employee_type' => $this->employeeType,
    ]);

    $this->same = StaffMember::create([
        'full_name_en' => 'Same Dept',
        'email' => 'same.dept@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->department->id,
        'is_active' => true,
        'is_teaching_staff' => true,
        'employee_type' => $this->employeeType,
    ]);

    $this->otherPeer = StaffMember::create([
        'full_name_en' => 'Other Dept Peer',
        'email' => 'other.peer@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->otherDept->id,
        'is_active' => true,
        'is_teaching_staff' => true,
        'employee_type' => $this->employeeType,
    ]);

    $this->committee = app(\App\Services\Committees\CommitteeService::class)->createLocalCommittee($this->coordinator, [
        'department_id' => $this->department->id,
        'same_department_member_id' => $this->same->id,
        'other_department_member_id' => $this->otherPeer->id,
        'evaluation_period_id' => $this->period->id,
    ]);
});

function hodStaffPayload(string $email, string $name = 'New Colleague'): array
{
    return [
        'full_name_en' => $name,
        'email' => $email,
        'college_id' => test()->college->id,
        'department_id' => test()->department->id,
        'employee_type' => test()->employeeType,
        'is_teaching_staff' => true,
        'is_active' => true,
    ];
}

it('allows department head to list and manage colleagues in their department', function () {
    $this->actingAs($this->hod)
        ->get(route('staff.index'))
        ->assertOk()
        ->assertSee('Peer Teacher')
        ->assertDontSee('Outsider');

    $this->actingAs($this->hod)
        ->get(route('staff.show', $this->peer))
        ->assertOk();

    $this->actingAs($this->hod)
        ->get(route('staff.edit', $this->peer))
        ->assertOk();

    $this->actingAs($this->hod)
        ->get(route('staff.show', $this->outsider))
        ->assertForbidden();
});

it('lets department head add staff and sync committee evaluations', function () {
    $this->actingAs($this->hod)
        ->post(route('staff.store'), hodStaffPayload('new.colleague@uoz.edu.krd'))
        ->assertRedirect(route('staff.index'));

    $created = StaffMember::where('email', 'new.colleague@uoz.edu.krd')->first();
    expect($created)->not->toBeNull();

    expect(Evaluation::where('committee_id', $this->committee->id)
        ->where('evaluatee_staff_id', $created->id)
        ->count())->toBeGreaterThan(0);
});

it('updates evaluations when department head deactivates teaching staff', function () {
    expect(Evaluation::where('committee_id', $this->committee->id)
        ->where('evaluatee_staff_id', $this->peer->id)
        ->count())->toBeGreaterThan(0);

    $this->actingAs($this->hod)
        ->put(route('staff.update', $this->peer), array_merge(hodStaffPayload($this->peer->email, $this->peer->full_name_en), [
            'is_teaching_staff' => false,
        ]))
        ->assertRedirect(route('staff.index'));

    expect(Evaluation::where('committee_id', $this->committee->id)
        ->where('evaluatee_staff_id', $this->peer->id)
        ->count())->toBe(0);
});

it('lets department head delete colleague and remove evaluations', function () {
    $this->actingAs($this->hod)
        ->delete(route('staff.destroy', $this->peer))
        ->assertRedirect(route('staff.index'));

    expect(StaffMember::withTrashed()->find($this->peer->id)?->trashed())->toBeTrue();
    expect(Evaluation::where('evaluatee_staff_id', $this->peer->id)->count())->toBe(0);
});

it('prevents department head from deleting their own head record', function () {
    $this->actingAs($this->hod)
        ->delete(route('staff.destroy', $this->headStaff))
        ->assertForbidden();
});

it('denies department head access without manage_department permission', function () {
    $this->hod->removeRole(RolePermissionSeeder::ROLE_DEPARTMENT_HEAD);

    $this->actingAs($this->hod)
        ->get(route('staff.index'))
        ->assertForbidden();
});
