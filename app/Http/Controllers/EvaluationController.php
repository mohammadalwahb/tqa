<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Services\Evaluations\EvaluationQuestionVisibilityService;
use App\Services\Evaluations\EvaluationSubmissionService;
use App\Services\Evaluations\SuperAdminEvaluationAssignmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class EvaluationController extends Controller
{
    public function __construct(
        private readonly EvaluationSubmissionService $submission,
        private readonly SuperAdminEvaluationAssignmentService $superAdminEvaluations,
        private readonly EvaluationQuestionVisibilityService $questionVisibility,
    ) {
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

        $evaluations = $query->get();

        return view('evaluations.index', compact('evaluations'));
    }

    public function show(Request $request, Evaluation $evaluation): View
    {
        $this->authorize('view', $evaluation);

        $evaluation->load(['answers.question.category', 'answers.question.visibleToRoles', 'evaluatee', 'evaluator', 'form.questions.visibleToRoles', 'committee']);

        if ($this->questionVisibility->usesSuperAdminQuestionScope($evaluation, $request->query('from'))) {
            $visibleQuestionIds = $this->questionVisibility
                ->filterForSuperAdmin($evaluation->form->questions->where('is_enabled', true))
                ->pluck('id');

            $evaluation->setRelation(
                'answers',
                $evaluation->answers->whereIn('evaluation_question_id', $visibleQuestionIds)->values()
            );
        }

        $returnRoute = $request->query('from') === 'super-admin'
            ? route('super-admin.evaluations.index', ['period_id' => $evaluation->evaluation_period_id])
            : route('evaluations.index');

        $superAdminScope = $this->questionVisibility->usesSuperAdminQuestionScope($evaluation, $request->query('from'));

        return view('evaluations.show', compact('evaluation', 'returnRoute', 'superAdminScope'));
    }

    public function edit(Request $request, Evaluation $evaluation): View|RedirectResponse
    {
        $this->authorize('update', $evaluation);

        $evaluation->load(['answers', 'form.questions.category', 'form.questions.visibleToRoles', 'evaluatee', 'committee']);

        $user = auth()->user();
        $userRoleIds = $user->roles->pluck('id')->all();
        $superAdminScope = $this->questionVisibility->usesSuperAdminQuestionScope($evaluation, $request->query('from'));

        $adminEdit = $user->can('evaluations.manage') && $evaluation->isSubmitted() && ! $superAdminScope;
        $superAdminEdit = $superAdminScope && $evaluation->isSubmitted();

        if ($superAdminScope) {
            $visibleQuestions = $this->questionVisibility
                ->filterForSuperAdmin($evaluation->form->questions->where('is_enabled', true))
                ->sortBy('sort_order')
                ->values();
        } else {
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
        }

        $answersByQuestion = $evaluation->answers->keyBy('evaluation_question_id');
        $returnRoute       = $this->evaluationReturnRoute($request, $evaluation);

        return view('evaluations.edit', [
            'evaluation'        => $evaluation,
            'visibleQuestions'  => $visibleQuestions,
            'answersByQuestion' => $answersByQuestion,
            'adminEdit'         => $adminEdit,
            'superAdminEdit'    => $superAdminEdit,
            'returnRoute'       => $returnRoute,
        ]);
    }

    public function update(Request $request, Evaluation $evaluation): RedirectResponse
    {
        $this->authorize('update', $evaluation);

        $superAdminScope = $this->questionVisibility->usesSuperAdminQuestionScope($evaluation, $request->query('from'));
        $adminOverride = ($request->user()->can('evaluations.manage') && $evaluation->isSubmitted() && ! $superAdminScope)
            || ($superAdminScope && $evaluation->isSubmitted());

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

        return redirect()->to($this->evaluationReturnUrl($request, $evaluation))
            ->with('success', __('messages.evaluation_submitted'));
    }

    private function evaluationReturnRoute(Request $request, Evaluation $evaluation): string
    {
        return $request->query('from') === 'super-admin'
            ? route('super-admin.evaluations.index', ['period_id' => $evaluation->evaluation_period_id])
            : route('evaluations.index');
    }

    private function evaluationReturnUrl(Request $request, Evaluation $evaluation): string
    {
        if ($request->query('from') === 'super-admin') {
            return route('super-admin.evaluations.index', [
                'period_id' => $evaluation->evaluation_period_id,
            ]);
        }

        return route('evaluations.index');
    }
}
