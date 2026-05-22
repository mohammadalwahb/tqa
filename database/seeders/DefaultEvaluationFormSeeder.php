<?php

namespace Database\Seeders;

use App\Models\EvaluationCategory;
use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DefaultEvaluationFormSeeder extends Seeder
{
    public function run(): void
    {
        $form = EvaluationForm::firstOrCreate(
            ['name' => 'Default Teaching Staff Evaluation'],
            [
                'description' => 'Default rubric used to evaluate teaching staff members.',
                'target_type' => 'staff',
                'is_active'   => true,
            ]
        );

        if ($form->questions()->count() > 0) {
            return;
        }

        $localRole = Role::where('name', RolePermissionSeeder::ROLE_LOCAL_COMMITTEE)->first();
        $hdRole    = Role::where('name', RolePermissionSeeder::ROLE_HD_COMMITTEE)->first();
        $qcRole    = Role::where('name', RolePermissionSeeder::ROLE_QUALITY_COORDINATOR)->first();

        $categories = [
            'Teaching & Pedagogy' => [
                ['type' => 'rating', 'text' => 'Clarity of teaching and explanation of concepts.'],
                ['type' => 'rating', 'text' => 'Preparedness and organization of lectures.'],
                ['type' => 'rating', 'text' => 'Use of modern teaching methods and tools.'],
            ],
            'Engagement & Communication' => [
                ['type' => 'rating', 'text' => 'Ability to engage students during lectures.'],
                ['type' => 'rating', 'text' => 'Responsiveness to student questions and feedback.'],
            ],
            'Professionalism' => [
                ['type' => 'rating', 'text' => 'Punctuality and respect for class schedule.'],
                ['type' => 'rating', 'text' => 'Adherence to academic policies and ethics.'],
            ],
            'Research & Development' => [
                ['type' => 'rating', 'text' => 'Contribution to research and publications.'],
                ['type' => 'rating', 'text' => 'Engagement with continuing professional development.'],
            ],
            'Comments' => [
                ['type' => 'text',   'text' => 'Strengths observed during the evaluation period.'],
                ['type' => 'text',   'text' => 'Areas for improvement.'],
                ['type' => 'text',   'text' => 'Additional notes (private).', 'roles' => [$hdRole, $qcRole]],
            ],
        ];

        $catOrder = 0;
        $qOrder = 0;
        foreach ($categories as $catName => $questions) {
            $cat = EvaluationCategory::create([
                'evaluation_form_id' => $form->id,
                'name'               => $catName,
                'sort_order'         => $catOrder++,
            ]);

            foreach ($questions as $q) {
                $question = EvaluationQuestion::create([
                    'evaluation_form_id'     => $form->id,
                    'evaluation_category_id' => $cat->id,
                    'type'                   => $q['type'],
                    'text'                   => $q['text'],
                    'sort_order'             => $qOrder++,
                    'is_required'            => $q['type'] === 'rating',
                    'is_enabled'             => true,
                ]);

                $roles = $q['roles'] ?? [$localRole, $hdRole, $qcRole];
                $roleIds = collect($roles)->filter()->pluck('id')->all();
                if (! empty($roleIds)) {
                    $question->visibleToRoles()->sync($roleIds);
                }
            }
        }
    }
}
