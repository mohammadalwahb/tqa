<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuperAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('super_admin')?->id;

        $domains = collect(config('tqa.allowed_email_domains', []))
            ->map(fn ($d) => '@' . $d)
            ->all();
        $endsWith = 'ends_with:' . implode(',', $domains);

        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => [
                'required', 'email', 'max:191', $endsWith,
                Rule::unique('users', 'email')->ignore($userId)->whereNull('deleted_at'),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        $domains = implode(', @', config('tqa.allowed_email_domains', []));

        return [
            'email.ends_with' => "Email must use one of the allowed domains: @{$domains}.",
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email'     => mb_strtolower(trim((string) $this->email)),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
