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
                throw new RuntimeException('The evaluation period is closed.');
            }
            if ($evaluation->isSubmitted()) {
                throw new RuntimeException('This evaluation has already been submitted.');
            }
        }

        if ($adminOverride && $finalize) {
            throw new RuntimeException('Administrators cannot re-submit evaluations. Save your changes instead.');
        }

        $form = $evaluation->form()->with('questions.visibleToRoles')->first();
        if (! $form) {
            throw new RuntimeException('Evaluation form is missing.');
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
                            throw new RuntimeException("Rating for question #{$question->id} must be between {$min} and {$max}.");
                        }
                        $ratingTotal += $ratingValue;
                        $ratingCount++;
                    } elseif ($finalize && $question->is_required) {
                        throw new RuntimeException("Question \"{$question->text}\" is required.");
                    }
                }

                if ($finalize && $question->is_required) {
                    if ($question->type === EvaluationQuestion::TYPE_TEXT && empty($textValue)) {
                        throw new RuntimeException("A response is required for: {$question->text}");
                    }
                    if ($question->type === EvaluationQuestion::TYPE_NUMBER && $numberValue === null) {
                        throw new RuntimeException("A numeric value is required for: {$question->text}");
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
