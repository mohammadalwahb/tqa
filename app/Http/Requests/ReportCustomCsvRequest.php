<?php

namespace App\Http\Requests;

use App\Models\EvaluationPeriod;
use App\Services\Reporting\ReportColumnCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReportCustomCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period_id' => ['required', 'exists:evaluation_periods,id'],
            'columns'   => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $period = EvaluationPeriod::find($this->input('period_id'));
            if (! $period) {
                return;
            }

            $catalog = app(ReportColumnCatalog::class);
            $allowed = collect($catalog->availableColumns($period))->pluck('key');
            $requested = collect($this->input('columns', []));

            if ($requested->diff($allowed)->isNotEmpty()) {
                $validator->errors()->add('columns', __('reports.csv_invalid_columns'));
            }
        });
    }
}
