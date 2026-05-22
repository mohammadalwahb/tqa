@extends('layouts.app')

@section('title', 'Organizational Roles')

@section('content')
<p class="text-muted">
    Assign Dean of College, Head of Department, and Quality Department Coordinator.
    Staff imported with position <strong>Dean</strong> or <strong>Head of Department</strong> are assigned here automatically; you can change them below.
    Quality College Coordinators are managed on the <a href="{{ route('coordinators.index') }}">Coordinators</a> page.
</p>

@foreach($colleges as $college)
    <div class="card table-card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ $college->name_en }}</h5>
            <span class="badge bg-info-subtle text-info-emphasis">{{ $college->departments->count() }} departments</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('org-roles.college.update', $college) }}" class="row g-2 align-items-end mb-4">
                @csrf
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Dean of College</label>
                    <select name="dean_staff_id" class="form-select">
                        <option value="">— Vacant —</option>
                        @foreach($college->staffMembers()->where('is_active', true)->orderBy('full_name_en')->get() as $staff)
                            <option value="{{ $staff->id }}" @selected($college->dean_staff_id == $staff->id)>
                                {{ $staff->full_name_en }} ({{ $staff->department?->name_en }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary"><i class="bi bi-save"></i> Save Dean</button>
                </div>
            </form>

            <h6 class="mb-3 text-muted text-uppercase small">Departments</h6>
            @foreach($college->departments as $dept)
                <form method="POST" action="{{ route('org-roles.department.update', $dept) }}"
                      class="row g-3 align-items-end p-3 border rounded mb-2">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">{{ $dept->name_en }}</label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Head of Department</label>
                        <select name="head_staff_id" class="form-select form-select-sm">
                            <option value="">— Vacant —</option>
                            @foreach($dept->staffMembers()->where('is_active', true)->orderBy('full_name_en')->get() as $staff)
                                <option value="{{ $staff->id }}" @selected($dept->head_staff_id == $staff->id)>
                                    {{ $staff->full_name_en }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Quality Department Coordinator</label>
                        <select name="quality_coordinator_staff_id" class="form-select form-select-sm">
                            <option value="">— Vacant —</option>
                            @foreach($dept->staffMembers()->where('is_active', true)->orderBy('full_name_en')->get() as $staff)
                                <option value="{{ $staff->id }}" @selected($dept->quality_coordinator_staff_id == $staff->id)>
                                    {{ $staff->full_name_en }}
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
