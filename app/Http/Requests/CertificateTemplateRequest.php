<?php

namespace App\Http\Requests;

use App\Models\CertificateTemplate;
use App\Models\EvaluationForm;
use App\Models\EvaluationPeriod;
use App\Services\Certificates\CertificateFieldCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CertificateTemplateRequest extends FormRequest
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
        $template = $this->route('certificate_template');

        return [
            'evaluation_period_id' => [
                'required',
                'exists:evaluation_periods,id',
                Rule::unique('certificate_templates', 'evaluation_period_id')
                    ->ignore($template?->id)
                    ->whereNull('deleted_at'),
            ],
            'evaluation_form_id'   => ['required', 'exists:evaluation_forms,id'],
            'background_image'     => ['nullable', 'image', 'max:5120'],
            'is_published'         => ['sometimes', 'boolean'],
            'layout_fields'        => ['nullable', 'array'],
            'layout_fields.*.key'  => ['required', 'string', 'max:120'],
            'layout_fields.*.content' => ['nullable', 'string', 'max:500'],
            'layout_fields.*.x'    => ['required', 'integer', 'min:0', 'max:5000'],
            'layout_fields.*.y'    => ['required', 'integer', 'min:0', 'max:5000'],
            'layout_fields.*.width'       => ['required', 'integer', 'min:40', 'max:5000'],
            'layout_fields.*.font_size'   => ['required', 'integer', 'min:8', 'max:120'],
            'layout_fields.*.font_weight' => ['required', 'in:normal,bold'],
            'layout_fields.*.color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'layout_fields.*.text_align'  => ['required', 'in:left,center,right'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $period = EvaluationPeriod::find($this->input('evaluation_period_id'));
            $form = EvaluationForm::find($this->input('evaluation_form_id'));
            if (! $form) {
                return;
            }

            $layoutFields = $this->input('layout_fields', []);
            if ($layoutFields === []) {
                return;
            }

            if (! app(CertificateFieldCatalog::class)->validateLayoutFields($form, $layoutFields, $period)) {
                $validator->errors()->add('layout_fields', __('certificates.invalid_fields'));
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('layout_json') && ! $this->has('layout_fields')) {
            $decoded = json_decode((string) $this->input('layout_json'), true);
            if (is_array($decoded)) {
                $this->merge(['layout_fields' => $decoded]);
            }
        }

        $this->merge([
            'is_published' => $this->boolean('is_published'),
        ]);
    }
}
