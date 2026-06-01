@extends('layouts.app')

@section('title', __('org_roles.title'))

@section('content')
<p class="text-muted">{{ __('org_roles.intro') }} {{ __('org_roles.import_hint') }}</p>

@foreach($colleges as $college)
    <div class="card table-card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ \App\Support\LocaleHelper::collegeDisplayName($college) }}</h5>
            <span class="badge bg-info-subtle text-info-emphasis">{{ __('org_roles.departments_count', ['count' => $college->departments->count()]) }}</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('org-roles.college.update', $college) }}" class="row g-2 align-items-end mb-4">
                @csrf
                <div class="col-md-8">
                    <label class="form-label fw-semibold">{{ __('org_roles.dean') }}</label>
                    <select name="dean_staff_id" class="form-select">
                        <option value="">{{ __('common.vacant') }}</option>
                        @foreach($college->staffMembers()->where('is_active', true)->orderBy('full_name_en')->get() as $staff)
                            <option value="{{ $staff->id }}" @selected($college->dean_staff_id == $staff->id)>
                                {{ \App\Support\LocaleHelper::staffDisplayName($staff) }} ({{ \App\Support\LocaleHelper::departmentDisplayName($staff->department) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary"><i class="bi bi-save"></i> {{ __('org_roles.save_dean') }}</button>
                </div>
            </form>

            <h6 class="mb-3 text-muted text-uppercase small">{{ __('org_roles.departments') }}</h6>
            @foreach($college->departments as $dept)
                <form method="POST" action="{{ route('org-roles.department.update', $dept) }}"
                      class="row g-3 align-items-end p-3 border rounded mb-2">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">{{ \App\Support\LocaleHelper::departmentDisplayName($dept) }}</label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">{{ __('org_roles.head') }}</label>
                        <select name="head_staff_id" class="form-select form-select-sm">
                            <option value="">{{ __('common.vacant') }}</option>
                            @foreach($dept->staffMembers()->where('is_active', true)->orderBy('full_name_en')->get() as $staff)
                                <option value="{{ $staff->id }}" @selected($dept->head_staff_id == $staff->id)>
                                    {{ \App\Support\LocaleHelper::staffDisplayName($staff) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">{{ __('org_roles.quality_coordinator') }}</label>
                        <select name="quality_coordinator_staff_id" class="form-select form-select-sm">
                            <option value="">{{ __('common.vacant') }}</option>
                            @foreach($dept->staffMembers()->where('is_active', true)->orderBy('full_name_en')->get() as $staff)
                                <option value="{{ $staff->id }}" @selected($dept->quality_coordinator_staff_id == $staff->id)>
                                    {{ \App\Support\LocaleHelper::staffDisplayName($staff) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary btn-sm w-100"><i class="bi bi-save"></i></button>
                    </div>
                </form>
            @endforeach
        </div>
    </div>
@endforeach
@endsection
