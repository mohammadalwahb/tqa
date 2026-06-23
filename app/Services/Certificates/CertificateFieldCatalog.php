<?php

namespace App\Services\Certificates;

use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationScoreMetric;
use Illuminate\Support\Str;

class CertificateFieldCatalog
{
    /**
     * @return list<array{key:string, label:string, group:string}>
     */
    public function availableFields(EvaluationForm $form): array
    {
        $fields = [
            ['key' => 'full_name_en', 'label' => __('staff.full_name_en'), 'group' => 'staff'],
            ['key' => 'college', 'label' => __('reports.col_college_en'), 'group' => 'staff'],
            ['key' => 'department', 'label' => __('reports.col_department_en'), 'group' => 'staff'],
            ['key' => 'academic_title', 'label' => __('fields.academic_title'), 'group' => 'staff'],
        ];

        $form->loadMissing(['questions', 'scoreMetrics']);

        foreach ($form->questions->where('is_enabled', true)->sortBy('sort_order') as $question) {
            $fields[] = [
                'key'   => 'question:' . $question->id,
                'label' => Str::limit($question->text, 80),
                'group' => $question->isScorable() ? 'questions' : 'questions',
            ];
        }

        foreach ($form->scoreMetrics->sortBy('sort_order') as $metric) {
            $fields[] = [
                'key'   => 'metric:' . $metric->id,
                'label' => $metric->name,
                'group' => 'metrics',
            ];
        }

        return $fields;
    }

    /**
     * @return list<array{key:string, label:string, group:string}>
     */
    public function resolvePlacedFields(EvaluationForm $form, array $placedFields): array
    {
        $catalog = collect($this->availableFields($form))->keyBy('key');
        $resolved = [];

        foreach ($placedFields as $placed) {
            $key = (string) ($placed['key'] ?? '');
            $column = $catalog->get($key);
            if (! $column) {
                continue;
            }

            $resolved[] = array_merge($column, [
                'x'           => (int) ($placed['x'] ?? 0),
                'y'           => (int) ($placed['y'] ?? 0),
                'width'       => (int) ($placed['width'] ?? 300),
                'font_size'   => (int) ($placed['font_size'] ?? 20),
                'font_weight' => (string) ($placed['font_weight'] ?? 'normal'),
                'color'       => (string) ($placed['color'] ?? '#000000'),
                'text_align'  => (string) ($placed['text_align'] ?? 'left'),
            ]);
        }

        return $resolved;
    }

    /**
     * @param  list<string>  $keys
     */
    public function validateFieldKeys(EvaluationForm $form, array $keys): bool
    {
        $allowed = collect($this->availableFields($form))->pluck('key');

        return collect($keys)->diff($allowed)->isEmpty();
    }
}
