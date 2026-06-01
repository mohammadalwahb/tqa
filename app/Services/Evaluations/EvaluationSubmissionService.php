<?php

namespace App\Services\Evaluations;

use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationQuestion;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EvaluationSubmissionService
{
    /**
     * @param array<int, array{rating?:int|null, number?:float|null, text?:string|null}> $answers
     */
    public function saveAnswers(
        Evaluation $evaluation,
        array $answers,
        bool $finalize = false,
        bool $adminOverride = false,
    ): Evaluation {
        $period = $evaluation->period;

        if (! $adminOverride) {
            if (! $period || ! $period->isOpen()) {
                throw new RuntimeException(__('messages.evaluation_period_closed'));
            }
            if ($evaluation->isSubmitted()) {
                throw new RuntimeException(__('messages.evaluation_locked'));
            }
        }

        if ($adminOverride && $finalize) {
            throw new RuntimeException(__('messages.evaluation_admin_no_resubmit'));
        }

        $form = $evaluation->form()->with('questions.visibleToRoles')->first();
        if (! $form) {
            throw new RuntimeException(__('messages.evaluation_form_missing'));
        }

        $questions = $form->questions->where('is_enabled', true);

        if (! $adminOverride) {
            $evaluator = $evaluation->evaluator()->with('roles')->first();
            $roleIds   = $evaluator?->roles->pluck('id') ?? collect();

            $questions = $questions->filter(function ($question) use ($roleIds) {
                if ($question->visibleToRoles->isEmpty()) {
                    return true;
                }

                return $question->visibleToRoles->pluck('id')->intersect($roleIds)->isNotEmpty();
            });
        }

        $questions = $questions->keyBy('id');

        DB::transaction(function () use ($evaluation, $answers, $questions, $finalize) {
            $ratingTotal = 0;
            $ratingCount = 0;

            foreach ($questions as $question) {
                $payload = $answers[$question->id] ?? [];
                $ratingValue = isset($payload['rating']) && $payload['rating'] !== '' ? (int) $payload['rating'] : null;
                $numberValue = isset($payload['number']) && $payload['number'] !== '' ? (float) $payload['number'] : null;
                $textValue   = $payload['text'] ?? null;

                if ($question->type === EvaluationQuestion::TYPE_RATING) {
                    if ($ratingValue !== null) {
                        $min = (int) config('tqa.rating.min', 1);
                        $max = (int) config('tqa.rating.max', 5);
                        if ($ratingValue < $min || $ratingValue > $max) {
                            throw new RuntimeException(__('messages.evaluation_rating_range', ['id' => $question->id, 'min' => $min, 'max' => $max]));
                        }
                        $ratingTotal += $ratingValue;
                        $ratingCount++;
                    } elseif ($finalize && $question->is_required) {
                        throw new RuntimeException(__('messages.evaluation_question_required', ['text' => $question->text]));
                    }
                }

                if ($finalize && $question->is_required) {
                    if ($question->type === EvaluationQuestion::TYPE_TEXT && empty($textValue)) {
                        throw new RuntimeException(__('messages.evaluation_response_required', ['text' => $question->text]));
                    }
                    if ($question->type === EvaluationQuestion::TYPE_NUMBER && $numberValue === null) {
                        throw new RuntimeException(__('messages.evaluation_numeric_required', ['text' => $question->text]));
                    }
                }

                EvaluationAnswer::updateOrCreate(
                    [
                        'evaluation_id'           => $evaluation->id,
                        'evaluation_question_id'  => $question->id,
                    ],
                    [
                        'rating_value' => $ratingValue,
                        'number_value' => $numberValue,
                        'text_value'   => $textValue,
                    ]
                );
            }

            $totalScore = $ratingCount > 0 ? round($ratingTotal / $ratingCount, 2) : null;

            $evaluation->fill([
                'total_score'           => $totalScore,
                'rated_questions_count' => $ratingCount,
            ]);

            if ($finalize) {
                $evaluation->status       = Evaluation::STATUS_SUBMITTED;
                $evaluation->submitted_at = now();
            } elseif ($adminOverride && $evaluation->isSubmitted()) {
                $evaluation->status = Evaluation::STATUS_SUBMITTED;
            }

            $evaluation->save();
        });

        return $evaluation->fresh(['answers']);
    }
}
