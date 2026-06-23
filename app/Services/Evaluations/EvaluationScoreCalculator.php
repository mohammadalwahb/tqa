<?php

namespace App\Services\Evaluations;

use App\Models\Committee;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationCategory;
use App\Models\EvaluationForm;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationScoreMetric;
use App\Models\StaffMember;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class EvaluationScoreCalculator
{
    public function __construct(
        private readonly MetricGradeResolver $gradeResolver,
    ) {
    }
    /**
     * Build full score analytics for a staff member in a period.
     *
     * @return array{
     *     overall: ?float,
     *     by_category: array<int, array{
     *         category_id: ?int,
     *         name: string,
     *         average: ?float,
     *         question_count: int,
     *         questions: array<int, array{
     *             question_id: int,
     *             text: string,
     *             type: string,
     *             is_shared: bool,
     *             evaluator_count: int,
     *             average: ?float
     *         }>
     *     }>,
     *     by_question: array<int, array{
     *         question_id: int,
     *         text: string,
     *         type: string,
     *         category: ?string,
     *         is_shared: bool,
     *         count: int,
     *         average: ?float
     *     }>,
     *     extractions: array<int, array{
     *         metric_id: int,
     *         name: string,
     *         operation: string,
     *         value: ?float,
     *         question_ids: array<int, int>
     *     }>
     * }
     */
    public function staffAnalytics(StaffMember $staff, EvaluationPeriod $period): array
    {
        $evaluations = Evaluation::query()
            ->with($this->analyticsRelations())
            ->where('evaluatee_staff_id', $staff->id)
            ->where('evaluation_period_id', $period->id)
            ->where('status', Evaluation::STATUS_SUBMITTED)
            ->get();

        return $this->staffAnalyticsFromEvaluations($evaluations, $staff);
    }

    /**
     * @param  Collection<int, Evaluation>  $evaluations  Submitted evaluations for one staff member.
     */
    public function staffAnalyticsFromEvaluations(Collection $evaluations, StaffMember $staff): array
    {
        if ($evaluations->isEmpty()) {
            return $this->emptyAnalytics();
        }

        $form = $evaluations->first()->form;
        if (! $form) {
            return $this->emptyAnalytics();
        }

        $questions = $form->questions
            ->where('is_enabled', true)
            ->sortBy('sort_order')
            ->values();

        $questionAggregates = $this->aggregateQuestions($evaluations, $questions);

        $byCategory = $this->buildCategoryScores($form->categories, $questions, $questionAggregates);
        $categoryAverages = collect($byCategory)
            ->filter(fn (array $row) => $row['include_in_final_score'])
            ->pluck('average')
            ->filter(fn ($v) => $v !== null);

        $overall = $categoryAverages->isNotEmpty()
            ? round((float) $categoryAverages->avg(), 2)
            : null;

        $byQuestion = collect($questionAggregates)
            ->filter(fn ($row) => $row['question']->isScorable())
            ->map(fn (array $row) => [
                'question_id' => $row['question']->id,
                'text'        => $row['question']->text,
                'type'        => $row['question']->type,
                'category'    => $row['question']->category?->name,
                'is_shared'   => $row['is_shared'],
                'count'       => $row['evaluator_count'],
                'average'     => $row['average'],
            ])
            ->values()
            ->all();

        $extractions = collect($this->buildExtractions($form->scoreMetrics, $questionAggregates, $staff))
            ->filter(fn (array $row) => $row['show_in_reports'])
            ->values()
            ->all();

        return [
            'overall'     => $overall,
            'by_category' => $byCategory,
            'by_question' => $byQuestion,
            'extractions' => $extractions,
        ];
    }

    /**
     * Certificate field values for a specific form (all derived metrics, no report filter).
     *
     * @return array{
     *     extractions: array<int, array<string, mixed>>,
     *     by_question: array<int, array<string, mixed>>
     * }
     */
    public function certificateFieldData(StaffMember $staff, EvaluationPeriod $period, EvaluationForm $form): array
    {
        $evaluations = Evaluation::query()
            ->with([
                'answers.question',
                'committee.members.user.roles',
            ])
            ->where('evaluatee_staff_id', $staff->id)
            ->where('evaluation_period_id', $period->id)
            ->where('status', Evaluation::STATUS_SUBMITTED)
            ->get();

        if ($evaluations->isEmpty()) {
            return ['extractions' => [], 'by_question' => []];
        }

        $form->loadMissing([
            'questions' => fn ($query) => $query->where('is_enabled', true)->orderBy('sort_order'),
        ]);

        $metrics = $form->scoreMetrics()
            ->with(['questions', 'grades'])
            ->orderBy('sort_order')
            ->get();

        $questionAggregates = $this->aggregateQuestions($evaluations, $form->questions);

        $byQuestion = [];
        foreach ($questionAggregates as $questionId => $row) {
            if (! $row['question']->isScorable()) {
                continue;
            }
            $byQuestion[$questionId] = [
                'question_id' => $questionId,
                'average'     => $row['average'],
            ];
        }

        $extractions = [];
        foreach ($this->buildExtractions($metrics, $questionAggregates, $staff) as $row) {
            $extractions[(int) $row['metric_id']] = $row;
        }

        return [
            'extractions' => $extractions,
            'by_question' => $byQuestion,
        ];
    }

    /**
     * @param  Collection<int, Evaluation>  $evaluations
     * @param  Collection<int, EvaluationQuestion>  $questions
     * @return array<int, array{
     *     question: EvaluationQuestion,
     *     is_shared: bool,
     *     evaluator_count: int,
     *     average: ?float,
     *     values: array<int, float>
     * }>
     */
    public function aggregateQuestions(Collection $evaluations, Collection $questions): array
    {
        $aggregates = [];

        foreach ($questions as $question) {
            if (! $question->isScorable()) {
                continue;
            }

            $values = [];
            $evaluatorIds = [];

            foreach ($evaluations as $evaluation) {
                $answer = $evaluation->answers->firstWhere('evaluation_question_id', $question->id);
                $numeric = $this->numericValue($answer, $question);
                if ($numeric === null) {
                    continue;
                }
                $values[] = (float) $numeric;
                $evaluatorIds[$evaluation->evaluator_user_id] = true;
            }

            $committee = $this->resolveCommitteeForQuestion($evaluations, $question);
            $isShared = $committee
                ? $this->isSharedAmongEvaluators($question, $committee)
                : count($evaluatorIds) > 1;

            $aggregates[$question->id] = [
                'question'         => $question,
                'is_shared'        => $isShared,
                'evaluator_count'  => count($evaluatorIds),
                'values'           => $values,
                'average'          => count($values) > 0
                    ? round(array_sum($values) / count($values), 2)
                    : null,
            ];
        }

        return $aggregates;
    }

    public function isSharedAmongEvaluators(EvaluationQuestion $question, Committee $committee): bool
    {
        return $this->countEvaluatorsWhoCanAnswer($question, $committee) > 1;
    }

    public function countEvaluatorsWhoCanAnswer(EvaluationQuestion $question, Committee $committee): int
    {
        $members = $committee->members->whereNotNull('user_id');
        $visibleRoleIds = $question->visibleToRoles->pluck('id');

        if ($visibleRoleIds->isEmpty()) {
            return $members->count();
        }

        $committeeCount = $members->filter(function ($member) use ($visibleRoleIds) {
            if (! $member->user) {
                return false;
            }

            return $member->user->roles->pluck('id')->intersect($visibleRoleIds)->isNotEmpty();
        })->count();

        $superAdminRoleId = Role::query()
            ->where('name', RolePermissionSeeder::ROLE_SUPER_ADMIN)
            ->where('guard_name', 'web')
            ->value('id');

        if (! $superAdminRoleId || ! $visibleRoleIds->contains((int) $superAdminRoleId)) {
            return $committeeCount;
        }

        return $committeeCount + 1;
    }

    /**
     * Category score = mean of per-question aggregates in that category.
     *
     * @param  Collection<int, EvaluationCategory>  $categories
     * @param  Collection<int, EvaluationQuestion>  $questions
     * @param  array<int, array>  $questionAggregates
     * @return array<int, array>
     */
    private function buildCategoryScores(
        Collection $categories,
        Collection $questions,
        array $questionAggregates,
    ): array {
        $grouped = $questions->groupBy('evaluation_category_id');
        $categoryRows = [];

        $processCategory = function (
            ?int $categoryId,
            string $name,
            bool $includeInFinalScore = true,
        ) use ($grouped, $questionAggregates, &$categoryRows) {
            $categoryQuestions = $grouped->get($categoryId, collect())
                ->filter(fn (EvaluationQuestion $q) => $q->isScorable());

            $questionRows = [];
            $scores = [];

            foreach ($categoryQuestions as $question) {
                $agg = $questionAggregates[$question->id] ?? null;
                $avg = $agg['average'] ?? null;

                $questionRows[] = [
                    'question_id'     => $question->id,
                    'text'            => $question->text,
                    'type'            => $question->type,
                    'is_shared'       => (bool) ($agg['is_shared'] ?? false),
                    'evaluator_count' => (int) ($agg['evaluator_count'] ?? 0),
                    'average'         => $avg,
                ];

                if ($avg !== null) {
                    $scores[] = $avg;
                }
            }

            if ($categoryQuestions->isEmpty()) {
                return;
            }

            $categoryRows[] = [
                'category_id'            => $categoryId,
                'name'                   => $name,
                'include_in_final_score' => $includeInFinalScore,
                'average'                => count($scores) > 0
                    ? round(array_sum($scores) / count($scores), 2)
                    : null,
                'question_count'         => $categoryQuestions->count(),
                'questions'              => $questionRows,
            ];
        };

        foreach ($categories->sortBy('sort_order') as $category) {
            $processCategory(
                $category->id,
                $category->name,
                (bool) $category->include_in_final_score,
            );
        }

        $uncategorized = $grouped->get(null, collect())->filter(fn (EvaluationQuestion $q) => $q->isScorable());
        if ($uncategorized->isNotEmpty()) {
            $processCategory(null, 'General', true);
        }

        return $categoryRows;
    }

    /**
     * @param  Collection<int, EvaluationScoreMetric>  $metrics
     * @param  array<int, array>  $questionAggregates
     * @return array<int, array>
     */
    private function buildExtractions(Collection $metrics, array $questionAggregates, StaffMember $evaluatee): array
    {
        return $metrics->sortBy('sort_order')->map(function (EvaluationScoreMetric $metric) use ($questionAggregates, $evaluatee) {
            $values = [];
            $questionIds = [];

            foreach ($metric->questions as $question) {
                $questionIds[] = $question->id;
                $avg = $questionAggregates[$question->id]['average'] ?? null;
                if ($avg !== null) {
                    $values[] = $avg;
                }
            }

            $value = match ($metric->operation) {
                EvaluationScoreMetric::OPERATION_AVERAGE => count($values) > 0
                    ? round(array_sum($values) / count($values), 2)
                    : null,
                default => count($values) > 0
                    ? round(array_sum($values), 2)
                    : null,
            };

            $letter = $this->gradeResolver->resolve($value, $metric, $evaluatee);

            return [
                'metric_id'            => $metric->id,
                'name'                 => $metric->name,
                'operation'            => $metric->operation,
                'show_in_reports'      => (bool) $metric->show_in_reports,
                'grade_by_academic_title' => (bool) $metric->grade_by_academic_title,
                'value'                => $value,
                'letter_grade'         => $letter['label'] ?? null,
                'letter_range'         => $letter['range'] ?? null,
                'grades'          => $metric->grades->map(fn ($g) => [
                    'label'    => $g->label,
                    'range'    => $g->rangeLabel(),
                    'min_value' => (float) $g->min_value,
                    'max_value' => $g->max_value !== null ? (float) $g->max_value : null,
                ])->values()->all(),
                'question_ids'    => $questionIds,
            ];
        })->values()->all();
    }

    private function numericValue(?EvaluationAnswer $answer, EvaluationQuestion $question): ?float
    {
        if (! $answer) {
            return null;
        }

        return match ($question->type) {
            EvaluationQuestion::TYPE_RATING => $answer->rating_value !== null
                ? (float) $answer->rating_value
                : null,
            EvaluationQuestion::TYPE_NUMBER => $answer->number_value !== null
                ? (float) $answer->number_value
                : null,
            default => null,
        };
    }

    /**
     * @param  Collection<int, Evaluation>  $evaluations
     */
    public function isQuestionShared(Collection $evaluations, EvaluationQuestion $question): bool
    {
        $committee = $this->resolveCommitteeForQuestion($evaluations, $question);

        if (! $committee) {
            return false;
        }

        return $this->isSharedAmongEvaluators($question, $committee);
    }

    /**
     * @param  Collection<int, Evaluation>  $evaluations
     */
    public function resolveCommitteeForQuestion(Collection $evaluations, EvaluationQuestion $question): ?Committee
    {
        foreach ($evaluations->groupBy('committee_id') as $committeeEvaluations) {
            $committee = $committeeEvaluations->first()->committee;
            if (! $committee) {
                continue;
            }
            if ($this->countEvaluatorsWhoCanAnswer($question, $committee) > 0) {
                return $committee;
            }
        }

        return $evaluations->first()?->committee;
    }

    public function emptyAnalytics(): array
    {
        return [
            'overall'     => null,
            'by_category' => [],
            'by_question' => [],
            'extractions' => [],
        ];
    }

    /**
     * @return list<string>
     */
    private function analyticsRelations(): array
    {
        return [
            'answers.question',
            'committee.members.user.roles',
            'form.categories',
            'form.questions.category',
            'form.questions.visibleToRoles',
            'form.scoreMetrics.questions',
            'form.scoreMetrics.grades',
        ];
    }
}
