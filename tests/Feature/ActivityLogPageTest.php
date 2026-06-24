<?php

use App\Models\StaffMember;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->admin = User::role('Super Admin')->firstOrFail();

    $college = \App\Models\College::firstOrFail();
    $department = $college->departments()->firstOrFail();

    $this->staff = StaffMember::create([
        'full_name_en' => 'Activity Log Staff',
        'email' => 'activity-staff@uoz.edu.krd',
        'college_id' => $college->id,
        'department_id' => $department->id,
        'is_active' => true,
    ]);
});

it('shows activity subject as user or staff name', function () {
    $this->actingAs($this->admin);

    $staff = $this->staff;
    $originalName = $staff->full_name_en;
    $staff->update(['full_name_en' => 'Updated Staff Name']);

    $this->get(route('activity-log.index'))
        ->assertOk()
        ->assertSee('Updated Staff Name')
        ->assertSee(__('activity_log.subject_staff'));

    $staff->update(['full_name_en' => $originalName]);
});

it('filters activity log with advanced search', function () {
    activity()
        ->causedBy($this->admin)
        ->performedOn($this->admin)
        ->event('updated')
        ->log('Updated user profile for search test');

    $this->actingAs($this->admin)
        ->get(route('activity-log.index', [
            'q' => 'search test',
            'event' => 'updated',
            'subject_type' => User::class,
            'causer_q' => $this->admin->email,
        ]))
        ->assertOk()
        ->assertSee('search test')
        ->assertSee($this->admin->name);

    $this->actingAs($this->admin)
        ->get(route('activity-log.index', [
            'subject_q' => 'no-such-person-xyz',
        ]))
        ->assertOk()
        ->assertSee(__('common.no_matching'));
});

it('resolves activity subject label from logged properties when record is missing', function () {
    Activity::query()->create([
        'log_name' => 'default',
        'description' => 'deleted',
        'subject_type' => User::class,
        'subject_id' => 999999,
        'causer_type' => User::class,
        'causer_id' => $this->admin->id,
        'event' => 'deleted',
        'properties' => [
            'attributes' => [
                'name' => 'Archived User',
                'email' => 'archived@uoz.edu.krd',
            ],
        ],
    ]);

    $this->actingAs($this->admin)
        ->get(route('activity-log.index', ['subject_q' => 'Archived User']))
        ->assertOk()
        ->assertSee('Archived User');
});
