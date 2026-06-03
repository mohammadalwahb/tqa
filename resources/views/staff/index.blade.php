@extends('layouts.app')

@section('title', __('staff.title'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    <div class="d-flex gap-2">
        @can('import', App\Models\StaffMember::class)
            <a href="{{ route('staff.template') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-download"></i> {{ __('staff.download_template') }}
            </a>
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload"></i> {{ __('common.import_csv') }}
            </button>
        @endcan
        @can('create', App\Models\StaffMember::class)
            <a href="{{ route('staff.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> {{ __('staff.new') }}
            </a>
        @endcan
        @role('Super Admin')
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#purgeStaffModal">
                <i class="bi bi-trash3"></i> {{ __('staff.delete_all') }}
            </button>
        @endrole
    </div>
</div>

<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('staff.title') }}</h5>
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            @unless($departmentHeadMode ?? false)
                <div class="d-flex gap-2" data-college-department-filter>
                    <select name="college_id" data-college-select class="form-select form-select-sm">
                        <option value="">{{ __('common.all_colleges') }}</option>
                        @foreach($colleges as $c)
                            <option value="{{ $c->id }}" @selected(request('college_id') == $c->id)>{{ \App\Support\LocaleHelper::collegeDisplayName($c) }}</option>
                        @endforeach
                    </select>
                    <select name="department_id" data-department-select class="form-select form-select-sm">
                        <option value="">{{ __('common.all_departments') }}</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" data-college-id="{{ $d->college_id }}"
                                @selected(request('department_id') == $d->id)>{{ \App\Support\LocaleHelper::departmentDisplayName($d) }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <span class="badge bg-primary-subtle text-primary-emphasis align-self-center">
                    {{ \App\Support\LocaleHelper::departmentDisplayName($headedDepartment) }}
                </span>
            @endunless
            <input type="text" name="search" placeholder="{{ __('common.search_name_email') }}"
                   value="{{ request('search') }}" class="form-control form-control-sm">
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i></button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('staff.full_name') }}</th>
                        <th>{{ __('common.email') }}</th>
                        <th>{{ __('staff.college_department') }}</th>
                        <th>{{ __('staff.title_position') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($staff as $s)
                    <tr>
                        <td>
                            <strong>{{ \App\Support\LocaleHelper::staffDisplayName($s) }}</strong>
                            @if($s->full_name_ku)<br><small class="text-muted">{{ $s->full_name_ku }}</small>@endif
                        </td>
                        <td>{{ $s->email }}</td>
                        <td>
                            <small class="text-muted">{{ \App\Support\LocaleHelper::collegeDisplayName($s->college) }}</small><br>
                            {{ \App\Support\LocaleHelper::departmentDisplayName($s->department) }}
                        </td>
                        <td>
                            {{ $s->academic_title ?? '—' }}<br>
                            <small class="text-muted">{{ $s->position ?? '' }}</small>
                        </td>
                        <td>
                            @if($s->status)
                                <span class="badge bg-{{ $s->status->color }}">{{ $s->status->name }}</span>
                            @else — @endif
                        </td>
                        <td class="text-end">
                            @can('view', $s)
                                <a href="{{ route('staff.show', $s) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-eye"></i></a>
                            @endcan
                            @can('update', $s)
                                <a href="{{ route('staff.edit', $s) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('delete', $s)
                                <form action="{{ route('staff.destroy', $s) }}" method="POST" class="d-inline" data-confirm="{{ __('staff.confirm_delete') }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('staff.empty') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $staff->links() }}
    </div>
</div>

@can('import', App\Models\StaffMember::class)
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('staff.import') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">{{ __('staff.import_modal_title') }}</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted mb-2">{{ __('staff.import_help_1') }}</p>
                <p class="small text-muted mb-2">
                    {{ __('staff.import_help_2') }}
                    <strong>{{ __('nav.staff_options') }}</strong>.
                </p>
                <p class="small text-muted mb-2">{{ __('staff.import_help_3') }}</p>
                <p class="small text-muted mb-2">
                    {{ __('staff.import_help_4') }}
                    <a href="{{ route('org-roles.index') }}">{{ __('org_roles.title') }}</a>.
                </p>
                <ul class="small text-muted mb-3 ps-3">
                    @foreach(\App\Imports\StaffImportTemplate::HEADERS as $column)
                        <li>{{ $column }}</li>
                    @endforeach
                </ul>
                <input type="file" name="file" required class="form-control" accept=".csv,.xlsx,.xls,.txt">
            </div>
            <div class="modal-footer">
                <a href="{{ route('staff.template') }}" class="btn btn-outline-secondary me-auto">
                    <i class="bi bi-download"></i> {{ __('staff.download_template_btn') }}
                </a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-primary"><i class="bi bi-upload"></i> {{ __('staff.import_btn') }}</button>
            </div>
        </form>
    </div>
</div>
@endcan

@role('Super Admin')
<div class="modal fade" id="purgeStaffModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('staff.purge-all') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title text-danger">{{ __('staff.purge_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger fw-semibold">{{ __('staff.purge_warning') }}</p>
                <p class="small text-muted">{{ __('staff.purge_body') }}</p>
                <label class="form-label">{{ __('staff.purge_confirm_label') }} <code>{{ __('staff.purge_confirm_phrase') }}</code></label>
                <input type="text" name="confirmation" class="form-control @error('confirmation') is-invalid @enderror" required>
                @error('confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-danger">{{ __('staff.purge_btn') }}</button>
            </div>
        </form>
    </div>
</div>
@endrole
@unless($departmentHeadMode ?? false)
    @include('partials.college-department-filter')
@endunless
@endsection
