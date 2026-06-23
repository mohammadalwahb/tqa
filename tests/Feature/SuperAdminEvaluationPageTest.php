<?php

use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\User;
use App\Services\Committees\CommitteeService;
use App\Services\Evaluations\EvaluationSubmissionService;
use App\Services\Evaluations\SuperAdminEvaluationAssignmentService;
use App\Services\Reporting\StaffReportZipExporter;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $this->period = EvaluationPeriod::first();
    $this->college = \App\Models\College::first();
    $this->deptA = $this->college->departments()->first();
    $this->deptB = $this->college->departments()->where('id', '!=', $this->deptA->id)->first();

    $this->admin = User::role('Super Admin')->firstOrFail();

    $this->coordinator = User::create([
        'name'       => 'Coord',
        'email'      => 'coord-sa-page@uoz.edu.krd',
        'password'   => Hash::make(Str::random(40)),
        'college_id' => $this->college->id,
        'is_active'  => true,
    ]);
    $this->coordinator->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);

    $this->deptHead = \App\Models\StaffMember::create([
        'full_name_en'      => 'Head SA',
        'email'             => 'head-sa-page@uoz.edu.krd',
        'college_id'        => $this->college->id,
        'department_id'     => $this->deptA->id,
        'is_active'         => true,
        'is_teaching_staff' => false,
    ]);
    $this->deptA->update(['head_staff_id' => $this->deptHead->id]);

    $this->same = \App\Models\StaffMember::create([
        'full_name_en'      => 'Same SA',
        'email'             => 'same-sa-page@uoz.edu.krd',
        'college_id'        => $this->college->id,
        'department_id'     => $this->deptA->id,
        'is_active'         => true,
        'is_teaching_staff' => true,
    ]);

    $this->other = \App\Models\StaffMember::create([
        'full_name_en'      => 'Other SA',
        'email'             => 'other-sa-page@uoz.edu.krd',
        'college_id'        => $this->college->id,
        'department_id'     => $this->deptB->id,
        'is_active'         => true,
        'is_teaching_staff' => true,
    ]);

    $this->teacher = \App\Models\StaffMember::create([
        'full_name_en'      => 'Teacher SA',
        'email'             => 'teacher-sa-page@uoz.edu.krd',
        'college_id'        => $this->college->id,
        'department_id'     => $this->deptA->id,
        'is_active'         => true,
        'is_teaching_staff' => true,
    ]);

    $form = \App\Models\EvaluationForm::where('target_type', 'staff')->firstOrFail();
    $superAdminRole = \Spatie\Permission\Models\Role::where('name', RolePermissionSeeder::ROLE_SUPER_ADMIN)->firstOrFail();
    $form->questions()->first()?->visibleToRoles()->syncWithoutDetaching([$superAdminRole->id]);

    app(CommitteeService::class)->createLocalCommittee($this->coordinator, [
        'department_id'              => $this->deptA->id,
        'same_department_member_id' => $this->same->id,
        'other_department_member_id' => $this->other->id,
        'evaluation_period_id'       => $this->period->id,
    ]);
});

it('allows super admin to access the dedicated evaluations page', function () {
    $this->actingAs($this->admin)
        ->get(route('super-admin.evaluations.index', ['period_id' => $this->period->id]))
        ->assertOk()
        ->assertSee(__('super_admin_evaluations.title'));
});

it('forbids coordinators from the super admin evaluations page', function () {
    $this->actingAs($this->coordinator)
        ->get(route('super-admin.evaluations.index'))
        ->assertForbidden();
});

it('lists shared super admin evaluations on the dedicated page', function () {
    $shared = app(SuperAdminEvaluationAssignmentService::class)
        ->sharedEvaluationsForPeriod($this->period);

    expect($shared)->not->toBeEmpty();

    $response = $this->actingAs($this->admin)
        ->get(route('super-admin.evaluations.index', ['period_id' => $this->period->id]))
        ->assertOk();

    foreach ($shared as $evaluation) {
        $response->assertSee($evaluation->evaluatee?->full_name_en ?? '');
    }
});

it('lets super admin submit shared evaluations after the period closes', function () {
    $this->period->update([
        'start_date' => now()->subMonths(3)->toDateString(),
        'end_date'   => now()->subMonth()->toDateString(),
    ]);

    $evaluation = Evaluation::query()
        ->where('evaluation_period_id', $this->period->id)
        ->where('evaluatee_staff_id', $this->teacher->id)
        ->whereHas('evaluator.roles', fn ($q) => $q->where('name', RolePermissionSeeder::ROLE_SUPER_ADMIN))
        ->first();

    expect($evaluation)->not->toBeNull()
        ->and($this->period->fresh()->isOpen())->toBeFalse();

    $form = $evaluation->form()->with('questions')->first();
    $answers = [];
    foreach ($form->questions->where('is_enabled', true)->where('type', 'rating') as $question) {
        $answers[$question->id] = ['rating' => 4];
    }

    $this->actingAs($this->admin);

    $result = app(EvaluationSubmissionService::class)->saveAnswers($evaluation, $answers, finalize: true);

    expect($result->status)->toBe(Evaluation::STATUS_SUBMITTED);
});

it('creates a zip archive with one pdf per staff member', function () {
    $evaluation = Evaluation::query()
        ->where('evaluation_period_id', $this->period->id)
        ->where('evaluatee_staff_id', $this->teacher->id)
        ->whereHas('evaluator.roles', fn ($q) => $q->where('name', RolePermissionSeeder::ROLE_SUPER_ADMIN))
        ->first();

    expect($evaluation)->not->toBeNull();

    $form = $evaluation->form()->with('questions')->first();
    $answers = [];
    foreach ($form->questions->where('is_enabled', true)->where('type', 'rating') as $question) {
        $answers[$question->id] = ['rating' => 4];
    }

    app(EvaluationSubmissionService::class)->saveAnswers($evaluation, $answers, finalize: true);

    $zipPath = app(StaffReportZipExporter::class)->createZipForPeriod($this->period);

    expect(file_exists($zipPath))->toBeTrue();

    $zip = new ZipArchive();
    expect($zip->open($zipPath))->toBeTrue();
    expect($zip->numFiles)->toBeGreaterThan(0);
    $zip->close();

    @unlink($zipPath);
});
