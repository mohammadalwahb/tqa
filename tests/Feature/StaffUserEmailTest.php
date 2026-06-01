<?php

use App\Enums\StaffLookupField;
use App\Models\Department;
use App\Models\StaffLookupOption;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\Committees\CommitteeService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->artisan('db:seed', ['--class' => RolePermissionSeeder::class]);

    $this->admin = User::role('Super Admin')->firstOrFail();
    $this->department = Department::query()->firstOrFail();
    $this->employeeType = StaffLookupOption::query()
        ->forField(StaffLookupField::EmployeeType)
        ->active()
        ->value('name');
});

function staffPayload(StaffMember $staff, string $email): array
{
    return [
        'full_name_en' => $staff->full_name_en,
        'email' => $email,
        'college_id' => $staff->college_id,
        'department_id' => $staff->department_id,
        'employee_type' => test()->employeeType,
        'is_teaching_staff' => true,
        'is_active' => true,
    ];
}

it('allows reverting staff email when an orphan user still holds the old address', function () {
    $staff = StaffMember::create([
        'full_name_en' => 'Ziyad Staff',
        'email' => 'ziyad@uoz.edu.krd',
        'college_id' => $this->department->college_id,
        'department_id' => $this->department->id,
        'is_active' => true,
    ]);

    app(CommitteeService::class)->ensureUserForStaff($staff->fresh());
    $linkedUser = $staff->fresh()->user;
    expect($linkedUser)->not->toBeNull();

    $this->actingAs($this->admin)->put(
        route('staff.update', $staff),
        staffPayload($staff, 'media.editing@uoz.edu.krd')
    )->assertRedirect(route('staff.index'));

    $staff->refresh();
    expect($staff->email)->toBe('media.editing@uoz.edu.krd')
        ->and($staff->user?->email)->toBe('media.editing@uoz.edu.krd');

    User::create([
        'name' => 'Ziyad Google',
        'email' => 'ziyad@uoz.edu.krd',
        'password' => bcrypt('secret'),
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)->put(
        route('staff.update', $staff),
        staffPayload($staff, 'ziyad@uoz.edu.krd')
    )->assertRedirect(route('staff.index'));

    $staff->refresh();
    expect($staff->email)->toBe('ziyad@uoz.edu.krd')
        ->and($staff->user?->email)->toBe('ziyad@uoz.edu.krd')
        ->and(User::where('email', 'ziyad@uoz.edu.krd')->whereNull('deleted_at')->count())->toBe(1)
        ->and((int) $staff->user_id)->toBe((int) $linkedUser->id);
});

it('clears soft-deleted user holding target email before updating linked account', function () {
    $staff = StaffMember::create([
        'full_name_en' => 'Ziyad Staff',
        'email' => 'ziyad@uoz.edu.krd',
        'college_id' => $this->department->college_id,
        'department_id' => $this->department->id,
        'is_active' => true,
    ]);

    $linked = User::create([
        'name' => 'Ziyad Staff',
        'email' => 'ziyad@uoz.edu.krd',
        'password' => bcrypt('secret'),
        'staff_member_id' => $staff->id,
        'is_active' => true,
    ]);
    $staff->update(['user_id' => $linked->id]);

    $ghost = User::create([
        'name' => 'Old Media Login',
        'email' => 'media.editing@uoz.edu.krd',
        'password' => bcrypt('secret'),
        'is_active' => true,
    ]);
    $ghost->delete();

    $staff->update(['email' => 'media.editing@uoz.edu.krd']);
    app(CommitteeService::class)->ensureUserForStaff($staff->fresh());

    expect($linked->fresh()->email)->toBe('media.editing@uoz.edu.krd')
        ->and(User::withTrashed()->where('email', 'media.editing@uoz.edu.krd')->count())->toBe(1);
});

it('reclaims duplicate user on ensureUserForStaff when syncing email', function () {
    $staff = StaffMember::create([
        'full_name_en' => 'Head',
        'email' => 'media.editing@uoz.edu.krd',
        'college_id' => $this->department->college_id,
        'department_id' => $this->department->id,
        'is_active' => true,
    ]);

    $linked = User::create([
        'name' => 'Head',
        'email' => 'media.editing@uoz.edu.krd',
        'password' => bcrypt('secret'),
        'staff_member_id' => $staff->id,
        'is_active' => true,
    ]);
    $staff->update(['user_id' => $linked->id]);

    User::create([
        'name' => 'Old Login',
        'email' => 'ziyad@uoz.edu.krd',
        'password' => bcrypt('secret'),
        'is_active' => true,
    ]);

    $staff->update(['email' => 'ziyad@uoz.edu.krd']);
    app(CommitteeService::class)->ensureUserForStaff($staff->fresh());

    expect($staff->fresh()->user?->email)->toBe('ziyad@uoz.edu.krd')
        ->and(User::where('email', 'ziyad@uoz.edu.krd')->whereNull('deleted_at')->count())->toBe(1);
});
