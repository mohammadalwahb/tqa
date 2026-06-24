<?php

namespace App\Http\Requests;

use App\Models\College;
use App\Models\Committee;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('activity_log.view') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q'            => ['nullable', 'string', 'max:255'],
            'subject_q'    => ['nullable', 'string', 'max:255'],
            'causer_q'     => ['nullable', 'string', 'max:255'],
            'event'        => ['nullable', 'string', Rule::in(['created', 'updated', 'deleted'])],
            'subject_type' => ['nullable', 'string', Rule::in([
                User::class,
                StaffMember::class,
                College::class,
                Department::class,
                Committee::class,
                Evaluation::class,
            ])],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /**
     * @return array{
     *     q?: ?string,
     *     subject_q?: ?string,
     *     causer_q?: ?string,
     *     event?: ?string,
     *     subject_type?: ?string,
     *     date_from?: ?string,
     *     date_to?: ?string
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q'            => $validated['q'] ?? null,
            'subject_q'    => $validated['subject_q'] ?? null,
            'causer_q'     => $validated['causer_q'] ?? null,
            'event'        => $validated['event'] ?? null,
            'subject_type' => $validated['subject_type'] ?? null,
            'date_from'    => $validated['date_from'] ?? null,
            'date_to'      => $validated['date_to'] ?? null,
        ];
    }
}
