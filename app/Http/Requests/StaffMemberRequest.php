<?php



namespace App\Http\Requests;



use App\Models\StaffMember;

use App\Services\Staff\StaffAttributeValidator;

use App\Support\Utf8Helper;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;



class StaffMemberRequest extends FormRequest

{

    public function authorize(): bool

    {

        $staff = $this->routeStaff();



        if ($this->isMethod('POST')) {

            return $this->user()?->can('create', StaffMember::class) ?? false;

        }



        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {

            return $staff && ($this->user()?->can('update', $staff) ?? false);

        }



        return $this->user()?->can('viewAny', StaffMember::class) ?? false;

    }



    public function rules(): array

    {

        $staffId = $this->routeStaff()?->id;



        $domains = collect(config('tqa.allowed_email_domains', []))

            ->map(fn ($d) => '@' . $d)

            ->all();

        $endsWith = 'ends_with:' . implode(',', $domains);



        $rules = array_merge([

            'full_name_en'      => ['required', 'string', 'max:255'],

            'full_name_ku'      => ['nullable', 'string', 'max:255'],

            'email'             => [

                'required', 'email', 'max:191', $endsWith,

                Rule::unique('staff_members', 'email')->ignore($staffId)->whereNull('deleted_at'),

            ],

            'gender'            => ['nullable', 'in:male,female'],

            'date_of_birth'     => ['nullable', 'date', 'before:today'],

            'age'               => ['nullable', 'integer', 'min:18', 'max:120'],

            'college_id'        => ['required', 'exists:colleges,id'],

            'department_id'     => ['required', 'exists:departments,id'],

            'is_teaching_staff' => ['sometimes', 'boolean'],

            'is_active'         => ['sometimes', 'boolean'],

        ], app(StaffAttributeValidator::class)->rulesForStaffForm($staffId));



        if ($department = $this->user()?->headedDepartment()) {

            $rules['department_id'][] = Rule::in([$department->id]);

            $rules['college_id'][]    = Rule::in([(int) $department->college_id]);

        }



        return $rules;

    }



    public function prepareForValidation(): void

    {

        $merge = [

            'email'             => mb_strtolower(trim((string) $this->email)),

            'full_name_ku'      => Utf8Helper::toUtf8($this->input('full_name_ku')),

            'is_teaching_staff' => $this->boolean('is_teaching_staff', true),

            'is_active'         => $this->boolean('is_active', true),

        ];



        if ($department = $this->user()?->headedDepartment()) {

            $merge['department_id'] = $department->id;

            $merge['college_id']    = $department->college_id;

        }



        $this->merge($merge);

    }



    public function messages(): array

    {

        $domains = implode(', @', config('tqa.allowed_email_domains', []));



        return [

            'email.ends_with'      => "Email must use one of these allowed domains: @{$domains}.",

            'department_id.in'     => 'You can only manage staff in your own department.',

            'college_id.in'        => 'College must match your department.',

        ];

    }



    private function routeStaff(): ?StaffMember

    {

        $staff = $this->route('staff');



        return $staff instanceof StaffMember ? $staff : null;

    }

}

