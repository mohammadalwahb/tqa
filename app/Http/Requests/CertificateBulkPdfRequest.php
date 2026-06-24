<?php

namespace App\Http\Requests;

use App\Models\CertificateTemplate;
use App\Services\Certificates\CertificateBulkPdfService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CertificateBulkPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('certificates.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'download_all' => ['sometimes', 'boolean'],
            'staff_ids'    => ['required_unless:download_all,1,true', 'array', 'min:1'],
            'staff_ids.*'  => ['required', 'integer', 'exists:staff_members,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->boolean('download_all')) {
                return;
            }

            $template = $this->route('certificate_template');
            if (! $template instanceof CertificateTemplate) {
                return;
            }

            $allowed = app(CertificateBulkPdfService::class)->eligibleStaffIds($template);
            $requested = collect($this->input('staff_ids', []))->map(fn ($id) => (int) $id);

            if ($requested->diff($allowed)->isNotEmpty()) {
                $validator->errors()->add('staff_ids', __('certificates.bulk_invalid_staff'));
            }
        });
    }

    /**
     * @return list<int>
     */
    public function resolvedStaffIds(CertificateTemplate $template): array
    {
        if ($this->boolean('download_all')) {
            return app(CertificateBulkPdfService::class)
                ->eligibleStaffIds($template)
                ->all();
        }

        return collect($this->validated('staff_ids'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
