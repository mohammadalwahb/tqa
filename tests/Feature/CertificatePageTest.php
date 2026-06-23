<?php

use App\Models\CertificateTemplate;
use App\Models\Evaluation;
use App\Models\User;
use App\Services\Committees\CommitteeService;
use App\Services\Evaluations\EvaluationSubmissionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
    Storage::fake('local');

    $this->period = \App\Models\EvaluationPeriod::first();
    $this->college = \App\Models\College::first();
    $this->deptA = $this->college->departments()->first();
    $this->deptB = $this->college->departments()->where('id', '!=', $this->deptA->id)->first();
    $this->admin = User::role('Super Admin')->firstOrFail();
    $this->form = \App\Models\EvaluationForm::where('target_type', 'staff')->firstOrFail();

    $this->deptHead = \App\Models\StaffMember::create([
        'full_name_en' => 'Head Cert',
        'email' => 'head-cert@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptA->id,
        'is_active' => true,
    ]);
    $this->deptA->update(['head_staff_id' => $this->deptHead->id]);

    $this->coordinator = User::create([
        'name' => 'Coord Cert',
        'email' => 'coord-cert@uoz.edu.krd',
        'password' => bcrypt('password'),
        'college_id' => $this->college->id,
        'is_active' => true,
    ]);
    $this->coordinator->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);

    $this->teacher = \App\Models\StaffMember::create([
        'full_name_en' => 'Teacher Cert',
        'full_name_ku' => 'مامۆستا',
        'email' => 'teacher-cert@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptA->id,
        'academic_title' => 'Professor',
        'is_active' => true,
        'is_teaching_staff' => true,
    ]);

    $this->same = \App\Models\StaffMember::create([
        'full_name_en' => 'Same Cert',
        'email' => 'same-cert@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptA->id,
        'is_active' => true,
        'is_teaching_staff' => true,
    ]);

    $this->other = \App\Models\StaffMember::create([
        'full_name_en' => 'Other Cert',
        'email' => 'other-cert@uoz.edu.krd',
        'college_id' => $this->college->id,
        'department_id' => $this->deptB->id,
        'is_active' => true,
        'is_teaching_staff' => true,
    ]);

    $this->teacherUser = User::create([
        'name' => 'Teacher Cert',
        'email' => $this->teacher->email,
        'password' => bcrypt('password'),
        'staff_member_id' => $this->teacher->id,
        'is_active' => true,
    ]);
    $this->teacher->update(['user_id' => $this->teacherUser->id]);
    $this->teacherUser->assignRole(RolePermissionSeeder::ROLE_STAFF_MEMBER);

    app(CommitteeService::class)->createLocalCommittee($this->coordinator, [
        'department_id' => $this->deptA->id,
        'same_department_member_id' => $this->same->id,
        'other_department_member_id' => $this->other->id,
        'evaluation_period_id' => $this->period->id,
    ]);

    $evaluation = Evaluation::query()
        ->where('evaluation_period_id', $this->period->id)
        ->where('evaluatee_staff_id', $this->teacher->id)
        ->first();

    $answers = [];
    foreach ($evaluation->form->questions->where('is_enabled', true)->where('type', 'rating') as $question) {
        $answers[$question->id] = ['rating' => 4];
    }

    foreach (Evaluation::query()->where('evaluatee_staff_id', $this->teacher->id)->where('evaluation_period_id', $this->period->id)->get() as $eval) {
        app(EvaluationSubmissionService::class)->saveAnswers($eval, $answers, finalize: true);
    }
});

it('allows super admin to manage certificate templates', function () {
    $this->actingAs($this->admin)
        ->get(route('certificate-templates.index'))
        ->assertOk()
        ->assertSee(__('certificates.templates_title'));

    $response = $this->actingAs($this->admin)
        ->post(route('certificate-templates.store'), [
            'evaluation_period_id' => $this->period->id,
            'evaluation_form_id' => $this->form->id,
            'layout_json' => json_encode([
                ['key' => 'full_name_en', 'x' => 100, 'y' => 200, 'width' => 400, 'font_size' => 28, 'font_weight' => 'bold', 'color' => '#000000', 'text_align' => 'center'],
            ]),
            'is_published' => true,
            'background_image' => UploadedFile::fake()->image('bg.jpg', 1123, 794),
        ]);

    $response->assertRedirect();
    expect(CertificateTemplate::where('evaluation_period_id', $this->period->id)->exists())->toBeTrue();
});

it('lets staff view published certificate for their year', function () {
    CertificateTemplate::create([
        'evaluation_period_id' => $this->period->id,
        'evaluation_form_id' => $this->form->id,
        'layout' => ['fields' => [
            ['key' => 'full_name_en', 'x' => 100, 'y' => 200, 'width' => 400, 'font_size' => 28, 'font_weight' => 'bold', 'color' => '#000000', 'text_align' => 'center'],
        ]],
        'is_published' => true,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->teacherUser)
        ->get(route('certificates.index'))
        ->assertOk()
        ->assertSee($this->period->name);

    $this->actingAs($this->teacherUser)
        ->get(route('certificates.show', $this->period))
        ->assertOk()
        ->assertSee($this->teacher->full_name_en);
});

it('hides unpublished certificates from staff', function () {
    CertificateTemplate::create([
        'evaluation_period_id' => $this->period->id,
        'evaluation_form_id' => $this->form->id,
        'layout' => ['fields' => []],
        'is_published' => false,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->teacherUser)
        ->get(route('certificates.show', $this->period))
        ->assertForbidden();
});

it('allows super admin to preview staff certificate', function () {
    $template = CertificateTemplate::create([
        'evaluation_period_id' => $this->period->id,
        'evaluation_form_id' => $this->form->id,
        'layout' => ['fields' => [
            ['key' => 'full_name_en', 'x' => 100, 'y' => 200, 'width' => 400, 'font_size' => 28, 'font_weight' => 'bold', 'color' => '#000000', 'text_align' => 'center'],
        ]],
        'is_published' => true,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('certificate-templates.preview', [$template, $this->teacher]))
        ->assertOk()
        ->assertSee($this->teacher->full_name_en);
});

it('renders derived metric values on certificate even when hidden from reports', function () {
    $ratingQuestion = $this->form->questions()->where('type', 'rating')->where('is_enabled', true)->firstOrFail();

    $metric = \App\Models\EvaluationScoreMetric::create([
        'evaluation_form_id' => $this->form->id,
        'name' => 'Teaching Grade',
        'operation' => \App\Models\EvaluationScoreMetric::OPERATION_AVERAGE,
        'show_in_reports' => false,
        'sort_order' => 99,
    ]);
    $metric->questions()->sync([$ratingQuestion->id]);

    \App\Models\EvaluationScoreMetricGrade::create([
        'evaluation_score_metric_id' => $metric->id,
        'label' => 'A',
        'min_value' => 3.5,
        'max_value' => 5,
        'sort_order' => 1,
    ]);

    CertificateTemplate::create([
        'evaluation_period_id' => $this->period->id,
        'evaluation_form_id' => $this->form->id,
        'layout' => ['fields' => [
            ['key' => 'metric:' . $metric->id, 'x' => 100, 'y' => 300, 'width' => 120, 'height' => 48, 'font_size' => 24, 'font_weight' => 'bold', 'color' => '#000000', 'text_align' => 'center'],
        ]],
        'is_published' => true,
        'created_by' => $this->admin->id,
    ]);

    $this->actingAs($this->teacherUser)
        ->get(route('certificates.show', $this->period))
        ->assertOk()
        ->assertSee('A');

    $response = $this->actingAs($this->teacherUser)
        ->get(route('certificates.download.pdf', $this->period));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});
