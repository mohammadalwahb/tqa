<?php

namespace App\Http\Requests;

use App\Enums\StaffLookupField;
use App\Rules\UniqueAmongActiveStaffLookupOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffLookupOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('staff_options.manage') ?? false;
    }

    public function rules(): array
    {
        $option = $this->route('staff_option');
        $id     = is_object($option) ? $option->id : null;
        $field  = $this->input('field', is_object($option) ? $option->field?->value : null);

        return [
            'field'     => ['required', Rule::enum(StaffLookupField::class)],
            'name' => [
                'required',
                'string',
                'max:120',
                new UniqueAmongActiveStaffLookupOptions($field, $id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name'      => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
