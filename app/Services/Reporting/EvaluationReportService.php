<?php

namespace App\Services\Reporting;

use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationScoreMetric;
use App\Models\StaffMember;
use App\Services\Evaluations\EvaluationScoreCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EvaluationReportService
{
    public function __construct(
        private readonly EvaluationScoreCalculator $scores,
    ) {
    }

    /**
     * @return array{required:int, completed:int, percentage:float, period_id:int}
     */
    public function universityProgress(EvaluationPeriod $period): array
    {
        $required  = (int) Evaluation::where('evaluation_period_id', $period->id)->count();
        $completed = (int) Evaluation::where('evaluation_period_id', $period->id)
            ->where('status', Evaluation::STATUS_SUBMITTED)
            ->count();

        $percentage = $required > 0 ? round(($completed / $required) * 100, 2) : 0.0;

        return [
            'required'   => $required,
            'completed'  => $completed,
            'percentage' => $percentage,
            'period_id'  => $period->id,
        ];
    }

    /**
     * Lightweight staff completion rows for the reports index (SQL aggregates, no per-staff analytics).
     *
     * @return Collection<int, array{staff:StaffMember, required:int, completed:int, percentage:float, average:float|null, question_values:array<int, array<string, mixed>>}>
     */
    public function staffProgressSummary(EvaluationPeriod $period): Collection
    {
        return $this->buildStaffProgressSummary($period);
    }

    /**
     * @return Collection<int, array{staff:StaffMember, required:int, completed:int, percentage:float, average:float|null, question_values:array<int, array<string, mixed>>}>
     */
    private function buildStaffProgressSummary(EvaluationPeriod $period): Collection
    {
        $stats = Evaluation::query()
            ->where('evaluation_period_id', $period->id)
            ->selectRaw('evaluatee_staff_id')
            ->selectRaw('COUNT(*) as required')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', [Evaluation::STATUS_SUBMITTED])
            ->selectRaw('AVG(CASE WHEN status = ? THEN total_score ELSE NULL END) as average', [Evaluation::STATUS_SUBMITTED])
            ->groupBy('evaluatee_staff_id')
            ->get()
            ->keyBy('evaluatee_staff_id');

        if ($stats->isEmpty()) {
            return collect();
        }

        $staffMembers = StaffMember::with(['department.college'])
            ->whereIn('id', $stats->keys())
            ->orderBy('full_name_en')
            ->get();

        $reportQuestions = $this->reportQuestionColumns($period);
        $scorableIds     = $reportQuestions->filter(fn (EvaluationQuestion $q) => $q->isScorable())->pluck('id');

        $scorableAverages = $this->batchScorableQuestionAverages($period, $scorableIds);
        $textSummaries    = $this->batchTextAnswerSummaries($period, $reportQuestions, $stats->keys());

        return $staffMembers->map(function (StaffMember $staff) use ($stats, $reportQuestions, $scorableAverages, $textSummaries) {
            $stat       = $stats[$staff->id];
            $required   = (int) $stat->required;
            $completed  = (int) $stat->completed;
            $percentage = $required > 0 ? round(($completed / $required) * 100, 1) : 0.0;

            return [
                'staff'           => $staff,
                'required'        => $required,
                'completed'       => $completed,
                'percentage'      => $percentage,
                'average'         => $stat->average !== null ? round((float) $stat->average, 2) : null,
                'question_values' => $this->mapQuestionValuesFromBatch(
                    $staff->id,
                    $reportQuestions,
                    $scorableAverages,
                    $textSummaries,
                ),
            ];
        })->values();
    }

    /**
     * @param  Collection<int, int|string>  $questionIds
     * @return array<int, array<int, float>>
     */
    private function batchScorableQuestionAverages(EvaluationPeriod $period, Collection $questionIds): array
    {
        if ($questionIds->isEmpty()) {
            return [];
        }

        $rows = EvaluationAnswer::query()
            ->join('evaluations', 'evaluations.id', '=', 'evaluation_answers.evaluation_id')
            ->where('evaluations.evaluation_period_id', $period->id)
            ->where('evaluations.status', Evaluation::STATUS_SUBMITTED)
            ->whereIn('evaluation_answers.evaluation_question_id', $questionIds)
            ->selectRaw('evaluations.evaluatee_staff_id as staff_id')
            ->selectRaw('evaluation_answers.evaluation_question_id as question_id')
            ->selectRaw(
                'AVG(CASE WHEN evaluation_answers.rating_value IS NOT NULL THEN evaluation_answers.rating_value ELSE evaluation_answers.number_value END) as avg_value'
            )
            ->groupBy('evaluations.evaluatee_staff_id', 'evaluation_answers.evaluation_question_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->staff_id][(int) $row->question_id] = round((float) $row->avg_value, 2);
        }

        return $map;
    }

    /**
     * @param  Collection<int, EvaluationQuestion>  $reportQuestions
     * @param  array<int, array<int, float>>  $scorableAverages
     * @param  array<int, array<int, string>>  $textSummaries
     * @return array<int, array<string, mixed>>
     */
    private function mapQuestionValuesFromBatch(
        int $staffId,
        Collection $reportQuestions,
        array $scorableAverages,
        array $textSummaries,
    ): array {
        $values = [];

        foreach ($reportQuestions as $question) {
            if (! $question->isScorable()) {
                $values[$question->id] = [
                    'type' => 'text',
                    'text' => $textSummaries["{$staffId}:{$question->id}"] ?? null,
                ];

                continue;
            }

            $values[$question->id] = [
                'type'    => $question->type,
                'average' => $scorableAverages[$staffId][$question->id] ?? null,
            ];
        }

        return $values;
    }

    /**
     * @return Collection<int, EvaluationScoreMetric>
     */
    public function reportDerivedMetricColumns(EvaluationPeriod $period): Collection
    {
        $formIds = $this->formIdsForPeriod($period);

        if ($formIds->isEmpty()) {
            return collect();
        }

        return EvaluationScoreMetric::query()
            ->whereIn('evaluation_form_id', $formIds)
            ->where('show_in_reports', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, EvaluationQuestion>
     */
    public function reportQuestionColumns(EvaluationPeriod $period): Collection
    {
        $formIds = $this->formIdsForPeriod($period);

        if ($formIds->isEmpty()) {
            return collect();
        }

        return EvaluationQuestion::query()
            ->whereIn('evaluation_form_id', $formIds)
            ->where('show_in_reports', true)
            ->where('is_enabled', true)
            ->with('category')
            ->orderBy('evaluation_category_id')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return Collection<int, array{
     *     staff: StaffMember,
     *     required: int,
     *     completed: int,
     *     percentage: float,
     *     average: ?float,
     *     derived_metrics: array<int, array>,
     *     question_values: array<int, array>
     * }>
     */
    public function staffProgress(EvaluationPeriod $period): Collection
    {
        return $this->buildStaffProgress($period);
    }

    /**
     * @return Collection<int, array{staff:StaffMember, required:int, completed:int, percentage:float, average:float|null, question_values:array<int, array<string, mixed>>, derived_metrics:array<int, array<string, mixed>>}>
     */
    private function buildStaffProgress(EvaluationPeriod $period): Collection
    {
        $reportQuestions = $this->reportQuestionColumns($period);

        $allEvaluationsByStaff = Evaluation::query()
            ->where('evaluation_period_id', $period->id)
            ->get()
            ->groupBy('evaluatee_staff_id');

        if ($allEvaluationsByStaff->isEmpty()) {
            return collect();
        }

        $submittedByStaff = Evaluation::query()
            ->with([
                'answers.question',
                'committee.members.user.roles',
                'form.categories',
                'form.questions.category',
                'form.questions.visibleToRoles',
                'form.scoreMetrics.questions',
                'form.scoreMetrics.grades',
            ])
            ->where('evaluation_period_id', $period->id)
            ->where('status', Evaluation::STATUS_SUBMITTED)
            ->get()
            ->groupBy('evaluatee_staff_id');

        $staffMembers = StaffMember::query()
            ->with(['department.college'])
            ->whereIn('id', $allEvaluationsByStaff->keys())
            ->get()
            ->keyBy('id');

        $textSummaries = $this->batchTextAnswerSummaries($period, $reportQuestions, $staffMembers->keys());

        return $allEvaluationsByStaff->map(function ($evaluations, $staffId) use (
            $period,
            $reportQuestions,
            $submittedByStaff,
            $staffMembers,
            $textSummaries,
        ) {
            $staff = $staffMembers->get((int) $staffId);
            if (! $staff) {
                return null;
            }

            $required   = $evaluations->count();
            $completed  = $evaluations->where('status', Evaluation::STATUS_SUBMITTED)->count();
            $percentage = $required > 0 ? round(($completed / $required) * 100, 2) : 0.0;

            $submitted = $submittedByStaff->get((int) $staffId, collect());
            $analytics = $submitted->isEmpty()
                ? $this->scores->emptyAnalytics()
                : $this->scores->staffAnalyticsFromEvaluations($submitted, $staff);

            return [
                'staff'           => $staff,
                'required'        => $required,
                'completed'       => $completed,
                'percentage'      => $percentage,
                'average'         => $analytics['overall'],
                'derived_metrics' => collect($analytics['extractions'] ?? [])
                    ->keyBy('metric_id')
                    ->all(),
                'question_values' => $this->buildQuestionReportValues(
                    $staff,
                    $period,
                    $reportQuestions,
                    $analytics,
                    $textSummaries,
                ),
            ];
        })
            ->filter()
            ->sortByDesc('percentage')
            ->values();
    }

    /**
     * @return array{
     *     overall:?float,
     *     by_category: array,
     *     by_question: array,
     *     extractions: array
     * }
     */
    public function staffAnalytics(StaffMember $staff, EvaluationPeriod $period): array
    {
        return $this->scores->staffAnalytics($staff, $period);
    }

    /**
     * Data for per-staff PDF: evaluator columns, shared/private questions, derived metrics.
     *
     * @return array{
     *     has_data: bool,
     *     overall: ?float,
     *     evaluators: array<int, array{id: int, name: string, role: string}>,
     *     shared_questions: array<int, array>,
     *     private_questions: array<int, array>,
     *     derived_metrics: array<int, array>
     * }
     */
    public function staffEvaluatorPdfData(StaffMember $staff, EvaluationPeriod $period): array
    {
        $analytics = $this->scores->staffAnalytics($staff, $period);

        $evaluations = Evaluation::query()
            ->with([
                'answers',
                'evaluator.roles',
                'committee.members.user.roles',
                'form.questions.category',
                'form.questions.visibleToRoles',
            ])
            ->where('evaluatee_staff_id', $staff->id)
            ->where('evaluation_period_id', $period->id)
            ->where('status', Evaluation::STATUS_SUBMITTED)
            ->get();

        if ($evaluations->isEmpty()) {
            return [
                'has_data'          => false,
                'overall'           => null,
                'evaluators'        => [],
                'shared_questions'  => [],
                'private_questions' => [],
                'derived_metrics'   => [],
            ];
        }

        $form = $evaluations->first()->form;
        if (! $form) {
            return [
                'has_data'          => false,
                'overall'           => null,
                'evaluators'        => [],
                'shared_questions'  => [],
                'private_questions' => [],
                'derived_metrics'   => [],
            ];
        }

        $questions = $form->questions
            ->where('is_enabled', true)
            ->sortBy('sort_order')
            ->values();

        $questionAggregates = $this->scores->aggregateQuestions($evaluations, $questions);
        $evaluators         = $this->buildEvaluatorColumns($evaluations);

        $sharedQuestions  = [];
        $privateQuestions = [];

        foreach ($questions as $question) {
            $agg      = $questionAggregates[$question->id] ?? null;
            $isShared = $agg
                ? (bool) $agg['is_shared']
                : $this->scores->isQuestionShared($evaluations, $question);

            $values = [];
            foreach ($evaluators as $evaluator) {
                $evaluation = $evaluations->firstWhere('evaluator_user_id', $evaluator['id']);
                $answer     = $evaluation?->answers->firstWhere('evaluation_question_id', $question->id);
                $values[$evaluator['id']] = $this->formatAnswerDisplay($answer, $question);
            }

            $row = [
                'text'     => $question->text,
                'category' => $question->category?->name,
                'type'     => $question->type,
                'values'   => $values,
                'average'  => $agg['average'] ?? null,
            ];

            if ($isShared) {
                $sharedQuestions[] = $row;
            } else {
                $privateQuestions[] = $row;
            }
        }

        return [
            'has_data'          => true,
            'overall'           => $analytics['overall'],
            'evaluators'        => $evaluators,
            'shared_questions'  => $sharedQuestions,
            'private_questions' => $privateQuestions,
            'derived_metrics'   => $analytics['extractions'] ?? [],
        ];
    }

    /**
     * @param  Collection<int, Evaluation>  $evaluations
     * @return array<int, array{id: int, name: string, role: string}>
     */
    private function buildEvaluatorColumns(Collection $evaluations): array
    {
        return $evaluations
            ->unique('evaluator_user_id')
            ->sortBy(fn (Evaluation $evaluation) => $evaluation->evaluator?->name ?? '')
            ->map(function (Evaluation $evaluation) {
                $user = $evaluation->evaluator;
                $role = $user?->roles->first()?->name ?? '—';

                return [
                    'id'   => (int) $evaluation->evaluator_user_id,
                    'name' => $user?->name ?? 'Unknown',
                    'role' => $role,
                ];
            })
            ->values()
            ->all();
    }

    private function formatAnswerDisplay(?EvaluationAnswer $answer, EvaluationQuestion $question): ?string
    {
        if (! $answer) {
            return null;
        }

        return match ($question->type) {
            EvaluationQuestion::TYPE_RATING => $answer->rating_value !== null
                ? (string) $answer->rating_value
                : null,
            EvaluationQuestion::TYPE_NUMBER => $answer->number_value !== null
                ? number_format((float) $answer->number_value, 2)
                : null,
            EvaluationQuestion::TYPE_TEXT => $answer->text_value
                ? Str::limit($answer->text_value, 200)
                : null,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $analytics
     * @return array<int, array{type: string, average: ?float, count: int, text: ?string}>
     */
    public function buildQuestionReportValues(
        StaffMember $staff,
        EvaluationPeriod $period,
        Collection $reportQuestions,
        array $analytics,
        array $textSummaries = [],
    ): array {
        if ($reportQuestions->isEmpty()) {
            return [];
        }

        $byQuestion = collect($analytics['by_question'] ?? [])->keyBy('question_id');
        $values = [];

        foreach ($reportQuestions as $question) {
            if ($question->isScorable()) {
                $row = $byQuestion->get($question->id);
                $values[$question->id] = [
                    'type'    => $question->type,
                    'average' => $row['average'] ?? null,
                    'count'   => (int) ($row['count'] ?? 0),
                    'text'    => null,
                ];
            } else {
                $summaryKey = "{$staff->id}:{$question->id}";
                $values[$question->id] = [
                    'type'    => EvaluationQuestion::TYPE_TEXT,
                    'average' => null,
                    'count'   => 0,
                    'text'    => $textSummaries[$summaryKey]
                        ?? $this->summarizeTextAnswers($staff, $period, $question->id),
                ];
            }
        }

        return $values;
    }

    /**
     * @param  Collection<int, int|string>  $staffIds
     * @return array<string, string>
     */
    private function batchTextAnswerSummaries(
        EvaluationPeriod $period,
        Collection $reportQuestions,
        Collection $staffIds,
    ): array {
        $textQuestionIds = $reportQuestions
            ->filter(fn (EvaluationQuestion $question) => ! $question->isScorable())
            ->pluck('id');

        if ($textQuestionIds->isEmpty() || $staffIds->isEmpty()) {
            return [];
        }

        $summaries = [];

        EvaluationAnswer::query()
            ->select([
                'evaluation_answers.evaluation_question_id',
                'evaluation_answers.text_value',
                'evaluations.evaluatee_staff_id',
            ])
            ->join('evaluations', 'evaluations.id', '=', 'evaluation_answers.evaluation_id')
            ->whereIn('evaluation_answers.evaluation_question_id', $textQuestionIds)
            ->whereIn('evaluations.evaluatee_staff_id', $staffIds)
            ->where('evaluations.evaluation_period_id', $period->id)
            ->where('evaluations.status', Evaluation::STATUS_SUBMITTED)
            ->whereNotNull('evaluation_answers.text_value')
            ->where('evaluation_answers.text_value', '!=', '')
            ->orderBy('evaluation_answers.id')
            ->chunk(500, function ($rows) use (&$summaries) {
                foreach ($rows as $row) {
                    $key = "{$row->evaluatee_staff_id}:{$row->evaluation_question_id}";
                    if (! isset($summaries[$key])) {
                        $summaries[$key] = Str::limit($row->text_value, 120);
                    }
                }
            });

        return $summaries;
    }

    /**
     * @return Collection<int, int>
     */
    private function formIdsForPeriod(EvaluationPeriod $period): Collection
    {
        return Evaluation::query()
            ->where('evaluation_period_id', $period->id)
            ->whereNotNull('evaluation_form_id')
            ->distinct()
            ->pluck('evaluation_form_id');
    }

    private function summarizeTextAnswers(StaffMember $staff, EvaluationPeriod $period, int $questionId): ?string
    {
        $text = EvaluationAnswer::query()
            ->where('evaluation_question_id', $questionId)
            ->whereNotNull('text_value')
            ->where('text_value', '!=', '')
            ->whereHas('evaluation', function ($q) use ($staff, $period) {
                $q->where('evaluatee_staff_id', $staff->id)
                    ->where('evaluation_period_id', $period->id)
                    ->where('status', Evaluation::STATUS_SUBMITTED);
            })
            ->value('text_value');

        return $text ? Str::limit($text, 120) : null;
    }
}
