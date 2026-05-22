<?php

use App\Models\College;
use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationForm;
use App\Models\EvaluationPeriod;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\Committees\CommitteeService;
use App\Services\Evaluations\EvaluationSubmissionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $this->college    = College::first();
    $this->deptA      = $this->college->departments()->first();
    $this->deptB      = $this->college->departments()->where('id', '!=', $this->deptA->id)->first();
    $this->period     = EvaluationPeriod::first();

    $this->coordinator = User::create([
        'name'       => 'Coord One',
        'email'      => 'coord@uoz.edu.krd',
        'password'   => Hash::make(Str::random(40)),
        'college_id' => $this->college->id,
        'is_active'  => true,
    ]);
    $this->coordinator->assignRole(RolePermissionSeeder::ROLE_QUALITY_COORDINATOR);

    $this->staffSame1 = StaffMember::create([
        'full_name_en'   => 'Same Dept Member 1',
        'email'          => 'same1@uoz.edu.krd',
        'college_id'     => $this->college->id,
        'department_id'  => $this->deptA->id,
        'is_active'      => true,
        'is_teaching_staff' => true,
    ]);
    $this->staffSame2 = StaffMember::create([
        'full_name_en'   => 'Same Dept Member 2',
        'email'          => 'same2@uoz.edu.krd',
        'college_id'     => $this->college->id,
        'department_id'  => $this->deptA->id,
        'is_active'      => true,
        'is_teaching_staff' => true,
    ]);
    $this->staffOther = StaffMember::create([
        'full_name_en'   => 'Other Dept Member',
        'email'          => 'other@uoz.edu.krd',
        'college_id'     => $this->college->id,
        'department_id'  => $this->deptB->id,
        'is_active'      => true,
        'is_teaching_staff' => true,
    ]);
    $this->teaching = StaffMember::create([
        'full_name_en'   => 'Teaching Subject',
        'email'          => 'subject@uoz.edu.krd',
        'college_id'     => $this->college->id,
        'department_id'  => $this->deptA->id,
        'is_active'      => true,
        'is_teaching_staff' => true,
    ]);
});

it('creates a local committee with the right structure', function () {
    $service = app(CommitteeService::class);

    $committee = $service->createLocalCommittee($this->coordinator, [
        'department_id'              => $this->deptA->id,
        'same_department_member_ids' => [$this->staffSame1->id, $this->staffSame2->id],
        'other_department_member_id' => $this->staffOther->id,
        'evaluation_period_id'       => $this->period->id,
    ]);

    expect($committee->type)->toBe('local')
        ->and($committee->members)->toHaveCount(4)
        ->and($committee->department_id)->toBe($this->deptA->id);

    expect($committee->members->where('member_role', CommitteeMember::ROLE_QUALITY_COLLEGE_COORDINATOR)->count())->toBe(1);
    expect($committee->members->where('member_role', CommitteeMember::ROLE_SAME_DEPARTMENT_MEMBER)->count())->toBe(2);
    expect($committee->members->where('member_role', CommitteeMember::ROLE_OTHER_DEPARTMENT_MEMBER)->count())->toBe(1);

    expect(Evaluation::where('committee_id', $committee->id)->count())->toBeGreaterThan(0);
});

it('rejects duplicate same-dept and other-dept member', function () {
    $service = app(CommitteeService::class);

    expect(fn () => $service->createLocalCommittee($this->coordinator, [
        'department_id'              => $this->deptA->id,
        'same_department_member_ids' => [$this->staffSame1->id, $this->staffSame2->id],
        'other_department_member_id' => $this->staffSame1->id,
        'evaluation_period_id'       => $this->period->id,
    ]))->toThrow(RuntimeException::class);
});

it('rejects wrong-college coordinator', function () {
    $service = app(CommitteeService::class);
    $other = User::factory()->create();

    expect(fn () => $service->createLocalCommittee($other, [
        'department_id'              => $this->deptA->id,
        'same_department_member_ids' => [$this->staffSame1->id, $this->staffSame2->id],
        'other_department_member_id' => $this->staffOther->id,
        'evaluation_period_id'       => $this->period->id,
    ]))->toThrow(RuntimeException::class);
});

it('submits an evaluation and computes average score', function () {
    $service = app(CommitteeService::class);
    $committee = $service->createLocalCommittee($this->coordinator, [
        'department_id'              => $this->deptA->id,
        'same_department_member_ids' => [$this->staffSame1->id, $this->staffSame2->id],
        'other_department_member_id' => $this->staffOther->id,
        'evaluation_period_id'       => $this->period->id,
    ]);

    $evaluation = $committee->evaluations()
        ->where('evaluator_user_id', $this->coordinator->id)
        ->first();

    $form = EvaluationForm::find($evaluation->evaluation_form_id);
    $ratingQuestions = $form->questions()->where('type', 'rating')->where('is_enabled', true)->get();

    $answers = [];
    foreach ($ratingQuestions as $q) {
        $answers[$q->id] = ['rating' => 4];
    }

    $submission = app(EvaluationSubmissionService::class);
    $result = $submission->saveAnswers($evaluation, $answers, finalize: true);

    expect((float) $result->total_score)->toBe(4.0)
        ->and($result->status)->toBe('submitted')
        ->and($result->submitted_at)->not->toBeNull();
});
