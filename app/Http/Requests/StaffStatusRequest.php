<?php

namespace App\Http\Requests;

use App\Rules\UniqueAmongActiveStaffStatuses;
use Illuminate\Foundation\Http\FormRequest;

class StaffStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->can('staff_options.manage') || $user->can('staff_status.manage'));
    }

    public function rules(): array
    {
        $id = $this->route('staff_status')?->id ?? $this->route('staff_statuses')?->id ?? $this->route('staff-statuses')?->id;
        $param = $this->route('staff_statuse') ?? $this->route('staff_status');
        if (is_object($param)) {
            $id = $param->id;
        }

        return [
            'name' => ['required', 'string', 'max:120', new UniqueAmongActiveStaffStatuses($id)],
            'color'     => ['nullable', 'string', 'max:30'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'name'      => trim((string) $this->input('name')),
            'is_active' => $this->boolean('is_active', true),
            'color'     => $this->input('color', 'secondary'),
        ]);
    }
}
