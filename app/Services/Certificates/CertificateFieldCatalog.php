<?php

namespace App\Services\Certificates;

use App\Models\EvaluationForm;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationScoreMetric;
use Illuminate\Support\Str;

class CertificateFieldCatalog
{
    /**
     * @return list<array{key:string, label:string, group:string}>
     */
    public function availableFields(EvaluationForm $form, ?EvaluationPeriod $period = null): array
    {
        $fields = [
            ['key' => 'full_name_en', 'label' => __('staff.full_name_en'), 'group' => 'staff'],
            ['key' => 'college', 'label' => __('reports.col_college_en'), 'group' => 'staff'],
            ['key' => 'department', 'label' => __('reports.col_department_en'), 'group' => 'staff'],
            ['key' => 'academic_title', 'label' => __('fields.academic_title'), 'group' => 'staff'],
        ];

        $form->loadMissing(['questions']);

        foreach ($form->questions->where('is_enabled', true)->sortBy('sort_order') as $question) {
            $fields[] = [
                'key'   => 'question:' . $question->id,
                'label' => Str::limit($question->text, 80),
                'group' => 'questions',
            ];
        }

        foreach ($this->derivedMetricsForForm($form, $period) as $metric) {
            $fields[] = [
                'key'   => 'metric:' . $metric->id,
                'label' => $metric->name,
                'group' => 'metrics',
            ];
        }

        return $fields;
    }

    /**
     * @return \Illuminate\Support\Collection<int, EvaluationScoreMetric>
     */
    private function derivedMetricsForForm(EvaluationForm $form, ?EvaluationPeriod $period): \Illuminate\Support\Collection
    {
        $formIds = collect([$form->id]);

        if ($period) {
            $periodFormIds = \App\Models\Committee::query()
                ->where('evaluation_period_id', $period->id)
                ->whereNotNull('evaluation_form_id')
                ->pluck('evaluation_form_id');

            $formIds = $formIds->merge($periodFormIds)->unique()->values();
        }

        return EvaluationScoreMetric::query()
            ->whereIn('evaluation_form_id', $formIds)
            ->orderBy('evaluation_form_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->unique('id')
            ->values();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resolvePlacedFields(EvaluationForm $form, array $placedFields, ?EvaluationPeriod $period = null): array
    {
        $catalog = collect($this->availableFields($form, $period))->keyBy('key');
        $resolved = [];

        foreach ($placedFields as $placed) {
            $key = (string) ($placed['key'] ?? '');

            if ($this->isStaticTextKey($key)) {
                $resolved[] = array_merge([
                    'key'     => $key,
                    'label'   => (string) ($placed['content'] ?? __('certificates.static_text')),
                    'group'   => 'text',
                    'content' => (string) ($placed['content'] ?? ''),
                ], $this->normalizePlacement($placed));

                continue;
            }

            $column = $catalog->get($key);
            if (! $column) {
                continue;
            }

            $resolved[] = array_merge($column, $this->normalizePlacement($placed));
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $placed
     * @return array<string, mixed>
     */
    private function normalizePlacement(array $placed): array
    {
        return [
            'x'           => (int) ($placed['x'] ?? 0),
            'y'           => (int) ($placed['y'] ?? 0),
            'width'       => (int) ($placed['width'] ?? 300),
            'font_size'   => (int) ($placed['font_size'] ?? 20),
            'font_weight' => (string) ($placed['font_weight'] ?? 'normal'),
            'color'       => (string) ($placed['color'] ?? '#000000'),
            'text_align'  => (string) ($placed['text_align'] ?? 'left'),
        ];
    }

    public function isStaticTextKey(string $key): bool
    {
        return str_starts_with($key, 'text:');
    }

    /**
     * @param  list<array<string, mixed>>  $layoutFields
     */
    public function validateLayoutFields(EvaluationForm $form, array $layoutFields, ?EvaluationPeriod $period = null): bool
    {
        $allowed = collect($this->availableFields($form, $period))->pluck('key');

        foreach ($layoutFields as $field) {
            $key = (string) ($field['key'] ?? '');

            if ($this->isStaticTextKey($key)) {
                if (trim((string) ($field['content'] ?? '')) === '') {
                    return false;
                }

                continue;
            }

            if (! $allowed->contains($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $keys
     */
    public function validateFieldKeys(EvaluationForm $form, array $keys, ?EvaluationPeriod $period = null): bool
    {
        $allowed = collect($this->availableFields($form, $period))->pluck('key');

        return collect($keys)->every(function (string $key) use ($allowed) {
            return $this->isStaticTextKey($key) || $allowed->contains($key);
        });
    }
}
