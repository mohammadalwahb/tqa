<?php

namespace App\Http\Requests;

use App\Support\Utf8Helper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollegeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('colleges.manage') ?? false;
    }

    public function rules(): array
    {
        $collegeId = $this->route('college')?->id;

        return [
            'name_en'     => ['required', 'string', 'max:255'],
            'name_ku'     => ['nullable', 'string', 'max:255'],
            'code'        => ['nullable', 'string', 'max:50', Rule::unique('colleges', 'code')->ignore($collegeId)->whereNull('deleted_at')],
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
