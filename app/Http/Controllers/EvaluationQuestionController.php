<?php

namespace App\Http\Controllers;

use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use App\Services\Evaluations\SuperAdminEvaluationAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluationQuestionController extends Controller
{
    public function store(Request $request, EvaluationForm $form): RedirectResponse
    {
        $data = $this->validatedData($request, $form);

        $question = $form->questions()->create([
            'evaluation_category_id' => $data['evaluation_category_id'] ?? null,
            'type'                   => $data['type'],
            'text'                   => $data['text'],
            'help_text'              => $data['help_text'] ?? null,
            'sort_order'             => ($form->questions()->max('sort_order') ?? -1) + 1,
            'is_required'            => $data['is_required'] ?? false,
            'is_enabled'             => $data['is_enabled'] ?? true,
            'show_in_reports'        => $data['show_in_reports'] ?? true,
        ]);

        $question->visibleToRoles()->sync($data['roles'] ?? []);

        app(SuperAdminEvaluationAssignmentService::class)->syncForForm($form);

        return back()->with('success', 'Question added.');
    }

    public function update(Request $request, EvaluationForm $form, EvaluationQuestion $question): RedirectResponse
    {
        abort_unless($question->evaluation_form_id === $form->id, 404);

        $data = $this->validatedData($request, $form);

        $question->update([
            'evaluation_category_id' => $data['evaluation_category_id'] ?? null,
            'type'                   => $data['type'],
            'text'                   => $data['text'],
            'help_text'              => $data['help_text'] ?? null,
            'is_required'            => $data['is_required'] ?? false,
            'is_enabled'             => $data['is_enabled'] ?? true,
            'show_in_reports'        => $data['show_in_reports'] ?? true,
        ]);

        $question->visibleToRoles()->sync($data['roles'] ?? []);

        app(SuperAdminEvaluationAssignmentService::class)->syncForForm($form);

        return back()->with('success', 'Question updated.');
    }

    public function destroy(EvaluationForm $form, EvaluationQuestion $question): RedirectResponse
    {
        abort_unless($question->evaluation_form_id === $form->id, 404);
        $question->delete();
        return back()->with('success', 'Question removed.');
    }

    public function reorder(Request $request, EvaluationForm $form): JsonResponse
    {
        $data = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'exists:evaluation_questions,id'],
        ]);

        DB::transaction(function () use ($data, $form) {
            foreach ($data['order'] as $index => $id) {
                EvaluationQuestion::where('id', $id)
                    ->where('evaluation_form_id', $form->id)
                    ->update(['sort_order' => $index]);
            }
        });

        return response()->json(['ok' => true]);
    }

    private function validatedData(Request $request, EvaluationForm $form): array
    {
        $data = $request->validate([
            'evaluation_category_id' => ['nullable', 'integer', 'exists:evaluation_categories,id'],
            'type'                   => ['required', 'in:rating,text,number'],
            'text'                   => ['required', 'string', 'max:50000'],
            'help_text'              => ['nullable', 'string', 'max:50000'],
            'is_required'            => ['sometimes', 'boolean'],
            'is_enabled'             => ['sometimes', 'boolean'],
            'show_in_reports'        => ['sometimes', 'boolean'],
            'roles'                  => ['array'],
            'roles.*'                => ['integer', 'exists:roles,id'],
        ]);

        $data['is_required']     = $request->boolean('is_required');
        $data['is_enabled']      = $request->boolean('is_enabled', true);
        $data['show_in_reports'] = $request->boolean('show_in_reports', true);

        return $data;
    }
}
