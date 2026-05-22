<?php

use App\Models\College;
use App\Models\Committee;
use App\Models\CommitteeMember;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationCategory;
use App\Models\EvaluationForm;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationScoreMetric;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\Evaluations\EvaluationScoreCalculator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->calculator = app(EvaluationScoreCalculator::class);
});

it('averages shared questions across evaluators and builds category score', function () {
    $localRole = Role::findByName(RolePermissionSeeder::ROLE_LOCAL_COMMITTEE, 'web');
    $hdRole = Role::findByName(RolePermissionSeeder::ROLE_HD_COMMITTEE, 'web');

    $college = College::create(['name_en' => 'Science', 'is_active' => true]);
    $department = Department::create(['college_id' => $college->id, 'name_en' => 'CS', 'is_active' => true]);
    $period = EvaluationPeriod::create([
        'name' => 'AY 2025-2026',
        'academic_year' => '2025-2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
        'is_active' => true,
    ]);
    $form = EvaluationForm::create(['name' => 'Test Form', 'target_type' => 'staff', 'is_active' => true]);
    $cat = EvaluationCategory::create(['evaluation_form_id' => $form->id, 'name' => 'Teaching', 'sort_order' => 0]);

    $qShared = EvaluationQuestion::create([
        'evaluation_form_id' => $form->id,
        'evaluation_category_id' => $cat->id,
        'type' => 'rating',
        'text' => 'Shared Q1',
        'sort_order' => 0,
        'is_required' => true,
        'is_enabled' => true,
    ]);
    $qShared->visibleToRoles()->sync([$localRole->id, $hdRole->id]);

    $qPrivate = EvaluationQuestion::create([
        'evaluation_form_id' => $form->id,
        'evaluation_category_id' => $cat->id,
        'type' => 'rating',
        'text' => 'Private Q2',
        'sort_order' => 1,
        'is_required' => true,
        'is_enabled' => true,
    ]);
    $qPrivate->visibleToRoles()->sync([$localRole->id]);

    $evaluatee = StaffMember::create([
        'full_name_en' => 'Teacher',
        'email' => 'teacher@uoz.edu.krd',
        'college_id' => $college->id,
        'department_id' => $department->id,
        'is_active' => true,
        'is_teaching_staff' => true,
    ]);

    $evaluatorA = User::factory()->create();
    $evaluatorA->assignRole($localRole);
    $evaluatorB = User::factory()->create();
    $evaluatorB->assignRole($hdRole);

    $committee = Committee::create([
        'type' => Committee::TYPE_LOCAL,
        'college_id' => $college->id,
        'department_id' => $department->id,
        'evaluation_period_id' => $period->id,
        'evaluation_form_id' => $form->id,
        'is_active' => true,
    ]);

    CommitteeMember::create(['committee_id' => $committee->id, 'user_id' => $evaluatorA->id, 'member_role' => 'm1']);
    CommitteeMember::create(['committee_id' => $committee->id, 'user_id' => $evaluatorB->id, 'member_role' => 'm2']);

    $evalA = Evaluation::create([
        'committee_id' => $committee->id,
        'evaluation_form_id' => $form->id,
        'evaluation_period_id' => $period->id,
        'evaluator_user_id' => $evaluatorA->id,
        'evaluatee_staff_id' => $evaluatee->id,
        'status' => Evaluation::STATUS_SUBMITTED,
        'submitted_at' => now(),
    ]);
    $evalB = Evaluation::create([
        'committee_id' => $committee->id,
        'evaluation_form_id' => $form->id,
        'evaluation_period_id' => $period->id,
        'evaluator_user_id' => $evaluatorB->id,
        'evaluatee_staff_id' => $evaluatee->id,
        'status' => Evaluation::STATUS_SUBMITTED,
        'submitted_at' => now(),
    ]);

    EvaluationAnswer::create(['evaluation_id' => $evalA->id, 'evaluation_question_id' => $qShared->id, 'rating_value' => 4]);
    EvaluationAnswer::create(['evaluation_id' => $evalB->id, 'evaluation_question_id' => $qShared->id, 'rating_value' => 6]);
    EvaluationAnswer::create(['evaluation_id' => $evalA->id, 'evaluation_question_id' => $qPrivate->id, 'rating_value' => 3]);

    $analytics = $this->calculator->staffAnalytics($evaluatee, $period);

    expect($analytics['by_category'][0]['questions'][0]['average'])->toBe(5.0)
        ->and($analytics['by_category'][0]['questions'][0]['is_shared'])->toBeTrue()
        ->and($analytics['by_category'][0]['questions'][1]['average'])->toBe(3.0)
        ->and($analytics['by_category'][0]['average'])->toBe(4.0)
        ->and($analytics['overall'])->toBe(4.0);
});

it('sums derived metric question averages', function () {
    $form = EvaluationForm::create(['name' => 'Metrics Form', 'target_type' => 'staff', 'is_active' => true]);
    $catA = EvaluationCategory::create(['evaluation_form_id' => $form->id, 'name' => 'A', 'sort_order' => 0]);
    $catB = EvaluationCategory::create(['evaluation_form_id' => $form->id, 'name' => 'B', 'sort_order' => 1]);

    $q4 = EvaluationQuestion::create([
        'evaluation_form_id' => $form->id,
        'evaluation_category_id' => $catB->id,
        'type' => 'number',
        'text' => 'Q4',
        'sort_order' => 0,
        'is_enabled' => true,
    ]);
    $q5 = EvaluationQuestion::create([
        'evaluation_form_id' => $form->id,
        'evaluation_category_id' => $catB->id,
        'type' => 'number',
        'text' => 'Q5',
        'sort_order' => 1,
        'is_enabled' => true,
    ]);

    $metric = EvaluationScoreMetric::create([
        'evaluation_form_id' => $form->id,
        'name' => 'Publications total',
        'operation' => 'sum',
    ]);
    $metric->questions()->sync([$q4->id, $q5->id]);

    $college = College::create(['name_en' => 'C', 'is_active' => true]);
    $department = Department::create(['college_id' => $college->id, 'name_en' => 'D', 'is_active' => true]);
    $period = EvaluationPeriod::create([
        'name' => 'P',
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
        'is_active' => true,
    ]);
    $staff = StaffMember::create([
        'full_name_en' => 'S',
        'email' => 's@uoz.edu.krd',
        'college_id' => $college->id,
        'department_id' => $department->id,
        'is_active' => true,
    ]);
    $user = User::factory()->create();
    $committee = Committee::create([
        'type' => Committee::TYPE_LOCAL,
        'college_id' => $college->id,
        'department_id' => $department->id,
        'evaluation_period_id' => $period->id,
        'evaluation_form_id' => $form->id,
        'is_active' => true,
    ]);
    CommitteeMember::create(['committee_id' => $committee->id, 'user_id' => $user->id, 'member_role' => 'm']);

    $evaluation = Evaluation::create([
        'committee_id' => $committee->id,
        'evaluation_form_id' => $form->id,
        'evaluation_period_id' => $period->id,
        'evaluator_user_id' => $user->id,
        'evaluatee_staff_id' => $staff->id,
        'status' => Evaluation::STATUS_SUBMITTED,
        'submitted_at' => now(),
    ]);

    EvaluationAnswer::create(['evaluation_id' => $evaluation->id, 'evaluation_question_id' => $q4->id, 'number_value' => 2]);
    EvaluationAnswer::create(['evaluation_id' => $evaluation->id, 'evaluation_question_id' => $q5->id, 'number_value' => 3]);

    $analytics = $this->calculator->staffAnalytics($staff, $period);

    expect($analytics['extractions'][0]['value'])->toBe(5.0);
});

it('excludes categories marked as not included from the final score', function () {
    $localRole = Role::findByName(RolePermissionSeeder::ROLE_LOCAL_COMMITTEE, 'web');

    $college = College::create(['name_en' => 'C', 'is_active' => true]);
    $department = Department::create(['college_id' => $college->id, 'name_en' => 'D', 'is_active' => true]);
    $period = EvaluationPeriod::create([
        'name' => 'P',
        'start_date' => now()->subDay(),
        'end_date' => now()->addDay(),
        'is_active' => true,
    ]);
    $form = EvaluationForm::create(['name' => 'F', 'target_type' => 'staff', 'is_active' => true]);

    $included = EvaluationCategory::create([
        'evaluation_form_id' => $form->id,
        'name' => 'Included',
        'sort_order' => 0,
        'include_in_final_score' => true,
    ]);
    $excluded = EvaluationCategory::create([
        'evaluation_form_id' => $form->id,
        'name' => 'Excluded',
        'sort_order' => 1,
        'include_in_final_score' => false,
    ]);

    $qIn = EvaluationQuestion::create([
        'evaluation_form_id' => $form->id,
        'evaluation_category_id' => $included->id,
        'type' => 'rating',
        'text' => 'In',
        'sort_order' => 0,
        'is_enabled' => true,
    ]);
    $qEx = EvaluationQuestion::create([
        'evaluation_form_id' => $form->id,
        'evaluation_category_id' => $excluded->id,
        'type' => 'rating',
        'text' => 'Ex',
        'sort_order' => 1,
        'is_enabled' => true,
    ]);
    $qIn->visibleToRoles()->sync([$localRole->id]);
    $qEx->visibleToRoles()->sync([$localRole->id]);

    $staff = StaffMember::create([
        'full_name_en' => 'S',
        'email' => 'excl@uoz.edu.krd',
        'college_id' => $college->id,
        'department_id' => $department->id,
        'is_active' => true,
    ]);
    $user = User::factory()->create();
    $user->assignRole($localRole);

    $committee = Committee::create([
        'type' => Committee::TYPE_LOCAL,
        'college_id' => $college->id,
        'department_id' => $department->id,
        'evaluation_period_id' => $period->id,
        'evaluation_form_id' => $form->id,
        'is_active' => true,
    ]);
    CommitteeMember::create(['committee_id' => $committee->id, 'user_id' => $user->id, 'member_role' => 'm']);

    $evaluation = Evaluation::create([
        'committee_id' => $committee->id,
        'evaluation_form_id' => $form->id,
        'evaluation_period_id' => $period->id,
        'evaluator_user_id' => $user->id,
        'evaluatee_staff_id' => $staff->id,
        'status' => Evaluation::STATUS_SUBMITTED,
        'submitted_at' => now(),
    ]);

    EvaluationAnswer::create(['evaluation_id' => $evaluation->id, 'evaluation_question_id' => $qIn->id, 'rating_value' => 4]);
    EvaluationAnswer::create(['evaluation_id' => $evaluation->id, 'evaluation_question_id' => $qEx->id, 'rating_value' => 1]);

    $analytics = $this->calculator->staffAnalytics($staff, $period);

    expect($analytics['overall'])->toBe(4.0)
        ->and(collect($analytics['by_category'])->firstWhere('name', 'Excluded')['include_in_final_score'])->toBeFalse();
});
