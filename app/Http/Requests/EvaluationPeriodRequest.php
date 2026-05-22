<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EvaluationPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('periods.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:120'],
            'academic_year' => ['nullable', 'string', 'max:30'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active', true)]);
    }
}
