<?php

use App\Models\Evaluation;
use App\Models\EvaluationForm;
use App\Models\User;
use App\Services\Committees\CommitteeService;
use App\Services\Evaluations\EvaluationQuestionVisibilityService;
use App\Services\Evaluations\SuperAdminEvaluationAssignmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $this->period = \App\Models\EvaluationPeriod::first();
    $this->college = \App\Models\College::first();
    $this->deptA = $this->college->departments()->first();
    $this->deptB = $this->college->departments()->where('id', '!=', $this->deptA->id)->first();
    $this->admin = User::role('Super Admin')->firstOrFail();

    $this->coordinator = User::create([
        'name' => 'Coord',
        'email' => 'coord-sa-q@uoz.edu.krd',
        'password' => Hash::make(Str::random(40)),
        'college_id' => $this->college->id,
        'is_active' => true,
    ]);
    $this->coordinator->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);

    $this->deptHead = \App\Models\StaffMember::create([
        'full_name_en' => 'Head',
        'email' => 'head-sa-q@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptA->id,
        'is_active' => true,
    ]);
    $this->deptA->update(['head_staff_id' => $this->deptHead->id]);

    $this->same = \App\Models\StaffMember::create([
        'full_name_en' => 'Same',
        'email' => 'same-sa-q@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptA->id,
        'is_active' => true,
        'is_teaching_staff' => true,
    ]);

    $this->other = \App\Models\StaffMember::create([
        'full_name_en' => 'Other',
        'email' => 'other-sa-q@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptB->id,
        'is_active' => true,
        'is_teaching_staff' => true,
    ]);

    $this->teacher = \App\Models\StaffMember::create([
        'full_name_en' => 'Teacher',
        'email' => 'teacher-sa-q@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptA->id,
        'is_active' => true,
        'is_teaching_staff' => true,
    ]);

    $form = EvaluationForm::where('target_type', 'staff')->firstOrFail();
    $superAdminRole = Role::where('name', RolePermissionSeeder::ROLE_SUPER_ADMIN)->firstOrFail();
    $localRole = Role::where('name', RolePermissionSeeder::ROLE_LOCAL_COMMITTEE)->firstOrFail();

    foreach ($form->questions as $question) {
        $question->visibleToRoles()->sync([$localRole->id]);
    }

    $form->questions()->first()?->visibleToRoles()->sync([$superAdminRole->id]);

    app(CommitteeService::class)->createLocalCommittee($this->coordinator, [
        'department_id' => $this->deptA->id,
        'same_department_member_id' => $this->same->id,
        'other_department_member_id' => $this->other->id,
        'evaluation_period_id' => $this->period->id,
    ]);
});

it('shows only super admin mapped questions when filling shared evaluations', function () {
    $evaluation = app(SuperAdminEvaluationAssignmentService::class)
        ->sharedEvaluationsForPeriod($this->period)
        ->first();

    expect($evaluation)->not->toBeNull();

    $response = $this->actingAs($this->admin)
        ->get(route('evaluations.edit', ['evaluation' => $evaluation, 'from' => 'super-admin']))
        ->assertOk();

    $localOnlyQuestion = $evaluation->form->questions()
        ->whereHas('visibleToRoles', fn ($q) => $q->where('name', RolePermissionSeeder::ROLE_LOCAL_COMMITTEE))
        ->whereDoesntHave('visibleToRoles', fn ($q) => $q->where('name', RolePermissionSeeder::ROLE_SUPER_ADMIN))
        ->first();

    $superAdminQuestion = $evaluation->form->questions()
        ->whereHas('visibleToRoles', fn ($q) => $q->where('name', RolePermissionSeeder::ROLE_SUPER_ADMIN))
        ->first();

    if ($localOnlyQuestion) {
        $response->assertDontSee($localOnlyQuestion->text, false);
    }

    if ($superAdminQuestion) {
        $response->assertSee($superAdminQuestion->text, false);
    }
});

it('allows super admin to export custom ordered csv from reports', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('reports.export.csv'), [
            'period_id' => $this->period->id,
            'columns' => ['email', 'staff_name', 'required'],
        ]);

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});

it('forbids coordinators from custom csv export', function () {
    $this->actingAs($this->coordinator)
        ->post(route('reports.export.csv'), [
            'period_id' => $this->period->id,
            'columns' => ['staff_name'],
        ])
        ->assertForbidden();
});
