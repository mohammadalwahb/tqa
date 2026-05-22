<?php

namespace App\Http\Requests;

use App\Support\Utf8Helper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('departments.manage') ?? false;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'college_id'  => ['required', 'exists:colleges,id'],
            'name_en'     => [
                'required', 'string', 'max:255',
                Rule::unique('departments', 'name_en')
                    ->where(fn ($q) => $q->where('college_id', $this->input('college_id')))
                    ->ignore($departmentId)
                    ->whereNull('deleted_at'),
            ],
            'name_ku'     => ['nullable', 'string', 'max:255'],
            'code'        => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name_ku'   => Utf8Helper::toUtf8($this->input('name_ku')),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
