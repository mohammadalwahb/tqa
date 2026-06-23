<?php

namespace App\Http\Requests;

use App\Models\EvaluationPeriod;
use App\Services\Reporting\StaffReportZipExporter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SuperAdminStaffZipRequest extends FormRequest
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
            'staff_ids' => ['required', 'array', 'min:1'],
            'staff_ids.*' => ['required', 'integer', 'exists:staff_members,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $period = EvaluationPeriod::find($this->input('period_id'));
            if (! $period) {
                return;
            }

            $allowed = app(StaffReportZipExporter::class)
                ->eligibleStaffRows($period)
                ->pluck('staff.id')
                ->map(fn ($id) => (int) $id);

            $requested = collect($this->input('staff_ids', []))->map(fn ($id) => (int) $id);

            if ($requested->diff($allowed)->isNotEmpty()) {
                $validator->errors()->add('staff_ids', __('super_admin_evaluations.zip_invalid_staff'));
            }
        });
    }
}
