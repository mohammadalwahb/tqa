<?php

namespace App\Http\Controllers;

use App\Http\Requests\EvaluationFormRequest;
use App\Models\EvaluationCategory;
use App\Models\EvaluationForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class EvaluationFormController extends Controller
{
    public function index(): View
    {
        $forms = EvaluationForm::withCount(['questions', 'categories'])->latest()->get();
        return view('forms.index', compact('forms'));
    }

    public function create(): View
    {
        return view('forms.form', ['form' => new EvaluationForm()]);
    }

    public function store(EvaluationFormRequest $request): RedirectResponse
    {
        $form = EvaluationForm::create(array_merge($request->validated(), [
            'created_by' => $request->user()->id,
        ]));
        return redirect()->route('forms.show', $form)->with('success', __('messages.form_created'));
    }

    public function show(EvaluationForm $form): View
    {
        $form->load([
            'categories',
            'questions.category',
            'questions.visibleToRoles',
            'scoreMetrics.questions',
            'scoreMetrics.grades',
        ]);

        return view('forms.show', [
            'form'                      => $form,
            'roles'                     => Role::orderBy('name')->get(),
            'academicTitleOptions'      => \App\Models\StaffLookupOption::query()
                ->forField(\App\Enums\StaffLookupField::AcademicTitle)
                ->active()
                ->orderBy('name')
                ->pluck('name'),
        ]);
    }

    public function edit(EvaluationForm $form): View
    {
        return view('forms.form', ['form' => $form]);
    }

    public function update(EvaluationFormRequest $request, EvaluationForm $form): RedirectResponse
    {
        $form->update($request->validated());
        return redirect()->route('forms.show', $form)->with('success', __('messages.form_updated'));
    }

    public function destroy(EvaluationForm $form): RedirectResponse
    {
        $form->delete();
        return redirect()->route('forms.index')->with('success', __('messages.form_deleted'));
    }

    public function storeCategory(Request $request, EvaluationForm $form): RedirectResponse
    {
        $data = $request->validate([
            'name'                   => ['required', 'string', 'max:120'],
            'include_in_final_score' => ['sometimes', 'boolean'],
        ]);

        $form->categories()->create([
            'name'                   => $data['name'],
            'sort_order'             => ($form->categories()->max('sort_order') ?? -1) + 1,
            'include_in_final_score' => $request->boolean('include_in_final_score', true),
        ]);

        return back()->with('success', __('messages.category_added'));
    }

    public function updateCategory(Request $request, EvaluationForm $form, EvaluationCategory $category): RedirectResponse
    {
        abort_unless((int) $category->evaluation_form_id === (int) $form->id, 404);

        $data = $request->validate([
            'name'                   => ['sometimes', 'string', 'max:120'],
            'include_in_final_score' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('name', $data)) {
            $category->name = $data['name'];
        }

        if ($request->has('include_in_final_score')) {
            $category->include_in_final_score = $request->boolean('include_in_final_score');
        }

        $category->save();

        return back()->with('success', __('messages.category_updated'));
    }

    public function destroyCategory(EvaluationForm $form, EvaluationCategory $category): RedirectResponse
    {
        abort_unless($category->evaluation_form_id === $form->id, 404);
        $category->questions()->update(['evaluation_category_id' => null]);
        $category->delete();

        return back()->with('success', __('messages.category_removed'));
    }
}
