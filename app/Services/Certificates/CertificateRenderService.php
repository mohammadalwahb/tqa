<?php

namespace App\Services\Certificates;

use App\Models\CertificateTemplate;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\StaffMember;
use App\Services\Evaluations\EvaluationScoreCalculator;
use Illuminate\Support\Str;

class CertificateRenderService
{
    public function __construct(
        private readonly CertificateFieldCatalog $fields,
        private readonly EvaluationScoreCalculator $scores,
    ) {
    }

    public function staffHasCertificateData(StaffMember $staff, CertificateTemplate $template): bool
    {
        return Evaluation::query()
            ->where('evaluatee_staff_id', $staff->id)
            ->where('evaluation_period_id', $template->evaluation_period_id)
            ->where('status', Evaluation::STATUS_SUBMITTED)
            ->exists();
    }

    public function staffMayView(CertificateTemplate $template, StaffMember $staff): bool
    {
        if (! $template->is_published) {
            return false;
        }

        return $this->staffHasCertificateData($staff, $template);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function renderFields(CertificateTemplate $template, StaffMember $staff): array
    {
        $staff->loadMissing(['college', 'department.college']);
        $template->loadMissing(['form', 'period']);

        $placed = $this->fields->resolvePlacedFields(
            $template->form,
            $template->placedFields(),
        );

        $analytics = $this->scores->staffAnalytics($staff, $template->period);
        $extractions = collect($analytics['extractions'] ?? [])->keyBy('metric_id');
        $questions = collect($analytics['by_question'] ?? [])->keyBy('question_id');

        return collect($placed)->map(function (array $field) use ($staff, $extractions, $questions) {
            return array_merge($field, [
                'value' => $this->resolveValue($field['key'], $staff, $extractions, $questions),
            ]);
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildViewData(CertificateTemplate $template, StaffMember $staff): array
    {
        return [
            'template' => $template,
            'staff'    => $staff,
            'period'   => $template->period,
            'fields'   => $this->renderFields($template, $staff),
            'width'    => $template->canvas_width ?: CertificateTemplate::CANVAS_WIDTH,
            'height'   => $template->canvas_height ?: CertificateTemplate::CANVAS_HEIGHT,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $extractions
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $questions
     */
    private function resolveValue(
        string $key,
        StaffMember $staff,
        \Illuminate\Support\Collection $extractions,
        \Illuminate\Support\Collection $questions,
    ): string {
        return match (true) {
            $key === 'full_name_en'   => (string) $staff->full_name_en,
            $key === 'college'        => (string) ($staff->college?->name_en ?? $staff->department?->college?->name_en ?? ''),
            $key === 'department'     => (string) ($staff->department?->name_en ?? ''),
            $key === 'academic_title' => (string) ($staff->academic_title ?? ''),
            str_starts_with($key, 'question:') => $this->questionValue($questions, (int) Str::after($key, 'question:')),
            str_starts_with($key, 'metric:')   => $this->metricValue($extractions, (int) Str::after($key, 'metric:')),
            default => '',
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $questions
     */
    private function questionValue(\Illuminate\Support\Collection $questions, int $questionId): string
    {
        $data = $questions->get($questionId);
        if (! $data) {
            return '';
        }

        if (($data['average'] ?? null) === null) {
            return '';
        }

        return number_format((float) $data['average'], 2);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $extractions
     */
    private function metricValue(\Illuminate\Support\Collection $extractions, int $metricId): string
    {
        $data = $extractions->get($metricId);
        if (! $data) {
            return '';
        }

        if (! empty($data['letter_grade'])) {
            return (string) $data['letter_grade'];
        }

        if (($data['value'] ?? null) === null) {
            return '';
        }

        return number_format((float) $data['value'], 2);
    }

    /**
     * @return \Illuminate\Support\Collection<int, CertificateTemplate>
     */
    public function publishedTemplatesForStaff(StaffMember $staff): \Illuminate\Support\Collection
    {
        return CertificateTemplate::query()
            ->with('period')
            ->where('is_published', true)
            ->orderByDesc(
                EvaluationPeriod::select('start_date')
                    ->whereColumn('evaluation_periods.id', 'certificate_templates.evaluation_period_id')
                    ->limit(1),
            )
            ->get()
            ->filter(fn (CertificateTemplate $template) => $this->staffMayView($template, $staff))
            ->values();
    }
}
