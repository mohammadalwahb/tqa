<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurgeAllStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'string', 'in:DELETE ALL STAFF'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation.in' => 'Type DELETE ALL STAFF (all caps) to confirm permanent deletion.',
        ];
    }
}
