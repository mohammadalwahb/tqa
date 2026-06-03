<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Services\Evaluations\EvaluationSubmissionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class EvaluationController extends Controller
{
    public function __construct(private readonly EvaluationSubmissionService $submission)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Evaluation::with([
                'evaluatee.department',
                'committee.department',
                'period',
                'evaluator.roles',
            ])
            ->orderByDesc('id');

        if ($user->can('evaluations.view_all')) {
            if (! $user->isSuperAdmin() && $user->college_id) {
                $query->whereHas('committee', fn ($q) => $q->where('college_id', $user->college_id));
            }
        } elseif ($user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('evaluator_user_id', $user->id)
                    ->orWhereHas('evaluator.roles', fn ($roleQuery) => $roleQuery->where(
                        'name',
                        RolePermissionSeeder::ROLE_SUPER_ADMIN
                    ));
            });
        } else {
            $query->where('evaluator_user_id', $user->id);
        }

        $evaluations = $query->paginate(25)->withQueryString();

        return view('evaluations.index', compact('evaluations'));
    }

    public function show(Evaluation $evaluation): View
    {
        $this->authorize('view', $evaluation);

        $evaluation->load(['answers.question.category', 'evaluatee', 'evaluator', 'form', 'committee']);

        return view('evaluations.show', compact('evaluation'));
    }

    public function edit(Evaluation $evaluation): View|RedirectResponse
    {
        $this->authorize('update', $evaluation);

        $evaluation->load(['answers', 'form.questions.category', 'form.questions.visibleToRoles', 'evaluatee', 'committee']);

        $user = auth()->user();
        $userRoleIds = $user->roles->pluck('id')->all();

        $adminEdit = $user->can('evaluations.manage') && $evaluation->isSubmitted();

        $visibleQuestions = $evaluation->form->questions
            ->where('is_enabled', true)
            ->filter(function ($q) use ($userRoleIds, $adminEdit) {
                if ($adminEdit) {
                    return true;
                }
                if ($q->visibleToRoles->isEmpty()) {
                    return true;
                }

                return $q->visibleToRoles->pluck('id')->intersect($userRoleIds)->isNotEmpty();
            })
            ->sortBy('sort_order')
            ->values();

        $answersByQuestion = $evaluation->answers->keyBy('evaluation_question_id');

        return view('evaluations.edit', [
            'evaluation'        => $evaluation,
            'visibleQuestions'  => $visibleQuestions,
            'answersByQuestion' => $answersByQuestion,
            'adminEdit'         => $adminEdit,
        ]);
    }

    public function update(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorize('update', $evaluation);

        $adminOverride = $request->user()->can('evaluations.manage') && $evaluation->isSubmitted();

        try {
            $this->submission->saveAnswers(
                $evaluation,
                $request->input('answers', []),
                finalize: false,
                adminOverride: $adminOverride,
            );
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $message = $adminOverride && $evaluation->isSubmitted()
            ? __('messages.evaluation_updated')
            : __('messages.evaluation_draft_saved');

        return back()->with('success', $message);
    }

    public function submit(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorize('update', $evaluation);

        if ($evaluation->isSubmitted() && $request->user()->can('evaluations.manage')) {
            return back()->with('error', __('messages.evaluation_already_submitted'));
        }

        try {
            $this->submission->saveAnswers($evaluation, $request->input('answers', []), finalize: true);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('evaluations.index')->with('success', __('messages.evaluation_submitted'));
    }
}
