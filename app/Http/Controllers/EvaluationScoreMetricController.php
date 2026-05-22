<?php



namespace App\Http\Controllers;



use App\Models\EvaluationForm;

use App\Models\EvaluationQuestion;

use App\Models\EvaluationScoreMetric;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Validation\Validator;



class EvaluationScoreMetricController extends Controller

{

    public function store(Request $request, EvaluationForm $form): RedirectResponse

    {

        $data = $this->validatedMetric($request);



        $questionIds = $this->resolveQuestionIds($form, $data['question_ids']);

        if ($questionIds->isEmpty()) {

            return back()->with('error', 'Select at least one question from this form.');

        }



        $metric = $form->scoreMetrics()->create([

            'name'                    => $data['name'],

            'operation'               => $data['operation'],

            'show_in_reports'         => $data['show_in_reports'],

            'grade_by_academic_title' => $data['grade_by_academic_title'],

            'sort_order'              => ($form->scoreMetrics()->max('sort_order') ?? -1) + 1,

        ]);



        $metric->questions()->sync($questionIds);

        $this->syncGrades($metric, $data);



        return back()->with('success', 'Derived metric added.');

    }



    public function update(Request $request, EvaluationForm $form, EvaluationScoreMetric $metric): RedirectResponse

    {

        abort_unless((int) $metric->evaluation_form_id === (int) $form->id, 404);



        $data = $this->validatedMetric($request);



        $questionIds = $this->resolveQuestionIds($form, $data['question_ids']);

        if ($questionIds->isEmpty()) {

            return back()->with('error', 'Select at least one question from this form.');

        }



        $metric->update([

            'name'                    => $data['name'],

            'operation'               => $data['operation'],

            'show_in_reports'         => $data['show_in_reports'],

            'grade_by_academic_title' => $data['grade_by_academic_title'],

        ]);



        $metric->questions()->sync($questionIds);

        $this->syncGrades($metric, $data);



        return back()->with('success', 'Derived metric updated.');

    }



    public function destroy(EvaluationForm $form, EvaluationScoreMetric $metric): RedirectResponse

    {

        abort_unless((int) $metric->evaluation_form_id === (int) $form->id, 404);

        $metric->delete();



        return back()->with('success', 'Derived metric removed.');

    }



    /**

     * @return array{

     *     name: string,

     *     operation: string,

     *     show_in_reports: bool,

     *     grade_by_academic_title: bool,

     *     question_ids: array<int>,

     *     grades: array<int, array{label: string, min_value: float, max_value: ?float}>,

     *     title_grades: array<int, array{academic_title: string, grades: array<int, array{label: string, min_value: float, max_value: ?float}>}>

     * }

     */

    private function validatedMetric(Request $request): array

    {

        $data = $request->validate([

            'name'                              => ['required', 'string', 'max:120'],

            'operation'                         => ['required', 'in:sum,average'],

            'show_in_reports'                   => ['sometimes', 'boolean'],

            'grade_by_academic_title'           => ['sometimes', 'boolean'],

            'question_ids'                      => ['required', 'array', 'min:1'],

            'question_ids.*'                    => ['integer', 'exists:evaluation_questions,id'],

            'grades'                            => ['nullable', 'array'],

            'grades.*.label'                    => ['nullable', 'string', 'max:20'],

            'grades.*.min_value'              => ['nullable', 'numeric'],

            'grades.*.max_value'                => ['nullable', 'numeric'],

            'title_grades'                      => ['nullable', 'array'],

            'title_grades.*.academic_title'     => ['nullable', 'string', 'max:120'],

            'title_grades.*.grades'             => ['nullable', 'array'],

            'title_grades.*.grades.*.label'     => ['nullable', 'string', 'max:20'],

            'title_grades.*.grades.*.min_value' => ['nullable', 'numeric'],

            'title_grades.*.grades.*.max_value' => ['nullable', 'numeric'],

        ]);



        $data['show_in_reports']         = $request->boolean('show_in_reports', true);

        $data['grade_by_academic_title'] = $request->boolean('grade_by_academic_title');

        $data['grades']                  = $this->normalizeGrades($data['grades'] ?? []);

        $data['title_grades']            = $this->normalizeTitleGrades($data['title_grades'] ?? []);



        $validator = validator($data, []);

        $validator->after(function (Validator $v) use ($data) {

            if ($data['grade_by_academic_title']) {

                if (count($data['title_grades']) === 0) {

                    $v->errors()->add('title_grades', 'Add at least one academic title with grade bands.');

                }



                $titles = [];

                foreach ($data['title_grades'] as $index => $group) {

                    if (in_array($group['academic_title'], $titles, true)) {

                        $v->errors()->add("title_grades.{$index}.academic_title", 'Each academic title can only be defined once.');

                    }

                    $titles[] = $group['academic_title'];



                    if (count($group['grades']) === 0) {

                        $v->errors()->add("title_grades.{$index}.grades", 'Add at least one grade for this academic title.');

                    }



                    $this->validateGrades($v, $group['grades'], "title_grades.{$index}.grades");

                }

            } else {

                $this->validateGrades($v, $data['grades'], 'grades');

            }

        });

        $validator->validate();



        return $data;

    }



    /**

     * @param  array<int, array{label?: string, min_value?: mixed, max_value?: mixed}>  $grades

     * @return array<int, array{label: string, min_value: float, max_value: ?float}>

     */

    private function normalizeGrades(array $grades): array

    {

        $normalized = [];



        foreach ($grades as $row) {

            $label = trim((string) ($row['label'] ?? ''));

            if ($label === '' || ! isset($row['min_value']) || $row['min_value'] === '') {

                continue;

            }



            $normalized[] = [

                'label'     => $label,

                'min_value' => (float) $row['min_value'],

                'max_value' => isset($row['max_value']) && $row['max_value'] !== ''

                    ? (float) $row['max_value']

                    : null,

            ];

        }



        return $normalized;

    }



    /**

     * @param  array<int, array{academic_title?: string, grades?: array}>  $titleGrades

     * @return array<int, array{academic_title: string, grades: array<int, array{label: string, min_value: float, max_value: ?float}>}>

     */

    private function normalizeTitleGrades(array $titleGrades): array

    {

        $normalized = [];



        foreach ($titleGrades as $group) {

            $title = trim((string) ($group['academic_title'] ?? ''));

            if ($title === '') {

                continue;

            }



            $grades = $this->normalizeGrades($group['grades'] ?? []);

            if ($grades === []) {

                continue;

            }



            $normalized[] = [

                'academic_title' => $title,

                'grades'         => $grades,

            ];

        }



        return $normalized;

    }



    /**

     * @param  array<int, array{label: string, min_value: float, max_value: ?float}>  $grades

     */

    private function validateGrades(Validator $validator, array $grades, string $prefix = 'grades'): void

    {

        foreach ($grades as $index => $grade) {

            if ($grade['max_value'] !== null && $grade['max_value'] < $grade['min_value']) {

                $validator->errors()->add("{$prefix}.{$index}.max_value", 'Maximum must be greater than or equal to minimum.');

            }

        }

    }



    /**

     * @param  array<int, int>  $questionIds

     */

    private function resolveQuestionIds(EvaluationForm $form, array $questionIds)

    {

        return EvaluationQuestion::query()

            ->where('evaluation_form_id', $form->id)

            ->whereIn('id', $questionIds)

            ->pluck('id');

    }



    /**

     * @param  array{

     *     grade_by_academic_title: bool,

     *     grades: array,

     *     title_grades: array

     * }  $data

     */

    private function syncGrades(EvaluationScoreMetric $metric, array $data): void

    {

        $metric->grades()->delete();



        if ($data['grade_by_academic_title']) {

            foreach ($data['title_grades'] as $titleIndex => $group) {

                foreach ($group['grades'] as $gradeIndex => $grade) {

                    $metric->grades()->create([

                        'academic_title'   => $group['academic_title'],

                        'title_sort_order' => $titleIndex,

                        'label'            => $grade['label'],

                        'min_value'        => $grade['min_value'],

                        'max_value'        => $grade['max_value'],

                        'sort_order'       => $gradeIndex,

                    ]);

                }

            }



            return;

        }



        foreach ($data['grades'] as $index => $grade) {

            $metric->grades()->create([

                'academic_title'   => null,

                'title_sort_order' => 0,

                'label'            => $grade['label'],

                'min_value'        => $grade['min_value'],

                'max_value'        => $grade['max_value'],

                'sort_order'       => $index,

            ]);

        }

    }

}

