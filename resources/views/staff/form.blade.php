@extends('layouts.app')

@section('title', $staff->exists ? 'Edit Staff Member' : 'New Staff Member')

@section('content')
<div class="card table-card">
    <div class="card-header"><h5 class="mb-0">{{ $staff->exists ? 'Edit Staff Member' : 'New Staff Member' }}</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ $staff->exists ? route('staff.update', $staff) : route('staff.store') }}">
            @csrf
            @if($staff->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name (English) <span class="text-danger">*</span></label>
                    <input type="text" name="full_name_en" class="form-control @error('full_name_en') is-invalid @enderror"
                           value="{{ old('full_name_en', $staff->full_name_en) }}" required>
                    @error('full_name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Full Name (Kurdish)</label>
                    <input type="text" name="full_name_ku" class="form-control"
                           value="{{ old('full_name_ku', $staff->full_name_ku) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $staff->email) }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">—</option>
                        <option value="male"   @selected(old('gender', $staff->gender) === 'male')>Male</option>
                        <option value="female" @selected(old('gender', $staff->gender) === 'female')>Female</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control"
                           value="{{ old('date_of_birth', $staff->date_of_birth?->toDateString()) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Age</label>
                    <input type="number" name="age" class="form-control" min="18" max="120"
                           value="{{ old('age', $staff->age) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Employee Type</label>
                    <select name="employee_type" class="form-select @error('employee_type') is-invalid @enderror">
                        <option value="">—</option>
                        @php $currentType = old('employee_type', $staff->employee_type); @endphp
                        @if($currentType && !($lookupOptions['employee_type'] ?? collect())->contains($currentType))
                            <option value="{{ $currentType }}" selected>{{ $currentType }} (legacy)</option>
                        @endif
                        @foreach($lookupOptions['employee_type'] ?? [] as $opt)
                            <option value="{{ $opt }}" @selected($currentType === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error('employee_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="staff_status_id" class="form-select @error('staff_status_id') is-invalid @enderror">
                        <option value="">—</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s->id }}" @selected(old('staff_status_id', $staff->staff_status_id) == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('staff_status_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @if($departmentHeadMode ?? false)
                    <input type="hidden" name="college_id" value="{{ $headedDepartment?->college_id }}">
                    <input type="hidden" name="department_id" value="{{ $headedDepartment?->id }}">
                    <div class="col-12">
                        <div class="alert alert-info py-2 small mb-0">
                            Colleagues are added to <strong>{{ $headedDepartment?->name_en }}</strong> and linked to existing
                            local evaluation committees when marked as teaching staff.
                        </div>
                    </div>
                @endif
                <div class="col-md-6 @if($departmentHeadMode ?? false) d-none @endif">
                    <label class="form-label">College <span class="text-danger">*</span></label>
                    <select name="college_id" id="collegeSelect" class="form-select @error('college_id') is-invalid @enderror" @unless($departmentHeadMode ?? false) required @endunless>
                        <option value="">— Choose —</option>
                        @foreach($colleges as $c)
                            <option value="{{ $c->id }}" @selected(old('college_id', $staff->college_id) == $c->id)>{{ $c->name_en }}</option>
                        @endforeach
                    </select>
                    @error('college_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 @if($departmentHeadMode ?? false) d-none @endif">
                    <label class="form-label">Department <span class="text-danger">*</span></label>
                    <select name="department_id" id="departmentSelect" class="form-select @error('department_id') is-invalid @enderror" @unless($departmentHeadMode ?? false) required @endunless>
                        <option value="">— Choose —</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" data-college="{{ $d->college_id }}"
                                @selected(old('department_id', $staff->department_id) == $d->id)>
                                {{ $d->name_en }} ({{ $d->college?->name_en }})
                            </option>
                        @endforeach
                    </select>
                    @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @foreach([
                    'qualification' => 'Qualification',
                    'academic_title' => 'Academic Title',
                    'position' => 'Position',
                ] as $field => $label)
                <div class="col-md-4">
                    <label class="form-label">{{ $label }}</label>
                    <select name="{{ $field }}" class="form-select @error($field) is-invalid @enderror">
                        <option value="">—</option>
                        @php $current = old($field, $staff->{$field}); @endphp
                        @if($current && !($lookupOptions[$field] ?? collect())->contains($current))
                            <option value="{{ $current }}" selected>{{ $current }} (legacy)</option>
                        @endif
                        @foreach($lookupOptions[$field] ?? [] as $opt)
                            <option value="{{ $opt }}" @selected($current === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                    @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @endforeach
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_teaching_staff" value="1"
                               id="isTeach" {{ old('is_teaching_staff', $staff->is_teaching_staff ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isTeach">Teaching staff</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="isActive" {{ old('is_active', $staff->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                <a href="{{ route('staff.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const college = document.getElementById('collegeSelect');
        const dept    = document.getElementById('departmentSelect');
        const options = Array.from(dept.options);

        function filter() {
            const cid = college.value;
            options.forEach(o => {
                if (!o.value) { o.hidden = false; return; }
                o.hidden = cid && o.dataset.college !== cid;
            });
            if (dept.selectedOptions[0]?.hidden) dept.value = '';
        }

        college.addEventListener('change', filter);
        filter();
    });
</script>
@endpush
@endsection
