@extends('layouts.app')

@section('title', 'Staff Members')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    <div class="d-flex gap-2">
        @can('import', App\Models\StaffMember::class)
            <a href="{{ route('staff.template') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-download"></i> Download CSV Template
            </a>
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload"></i> Import CSV
            </button>
        @endcan
        @can('create', App\Models\StaffMember::class)
            <a href="{{ route('staff.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> New Staff Member
            </a>
        @endcan
        @role('Super Admin')
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#purgeStaffModal">
                <i class="bi bi-trash3"></i> Delete all staff
            </button>
        @endrole
    </div>
</div>

<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">Staff Members</h5>
        <form method="GET" class="d-flex gap-2">
            @unless($departmentHeadMode ?? false)
                <select name="college_id" class="form-select form-select-sm">
                    <option value="">All colleges</option>
                    @foreach($colleges as $c)
                        <option value="{{ $c->id }}" @selected(request('college_id') == $c->id)>{{ $c->name_en }}</option>
                    @endforeach
                </select>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name_en }}</option>
                    @endforeach
                </select>
            @else
                <span class="badge bg-primary-subtle text-primary-emphasis align-self-center">
                    {{ $headedDepartment?->name_en }}
                </span>
            @endunless
            <input type="text" name="search" placeholder="Search name/email"
                   value="{{ request('search') }}" class="form-control form-control-sm">
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i></button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>College / Department</th>
                        <th>Title / Position</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($staff as $s)
                    <tr>
                        <td>
                            <strong>{{ $s->full_name_en }}</strong>
                            @if($s->full_name_ku)<br><small class="text-muted">{{ $s->full_name_ku }}</small>@endif
                        </td>
                        <td>{{ $s->email }}</td>
                        <td>
                            <small class="text-muted">{{ $s->college?->name_en }}</small><br>
                            {{ $s->department?->name_en }}
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
                                <form action="{{ route('staff.destroy', $s) }}" method="POST" class="d-inline" data-confirm="Delete staff member?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No staff members found.</td></tr>
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
            <div class="modal-header"><h5 class="modal-title">Import staff from CSV / Excel</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="small text-muted mb-2">
                    Use the CSV template columns exactly as listed. Institutional e-mail must use an allowed university domain.
                    Employee type, qualification, academic title, position, and status must match values configured under
                    <strong>Staff Field Options</strong>.
                    For Kurdish names, use the downloaded template or save your spreadsheet as <strong>CSV UTF-8</strong> (Excel: Save As → CSV UTF-8).
                    Position <strong>Dean</strong> or <strong>Head of Department</strong> automatically assigns organizational roles (editable under
                    <a href="{{ route('org-roles.index') }}">Organizational Roles</a>).
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
                    <i class="bi bi-download"></i> Download CSV template
                </a>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary"><i class="bi bi-upload"></i> Import</button>
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
                <h5 class="modal-title text-danger">Permanently delete all staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger fw-semibold">This cannot be undone.</p>
                <p class="small text-muted">
                    All staff records will be removed permanently, including soft-deleted rows.
                    Related evaluations will be deleted. Dean / Head assignments will be cleared.
                    User accounts are kept but unlinked from staff.
                </p>
                <label class="form-label">Type <code>DELETE ALL STAFF</code> to confirm</label>
                <input type="text" name="confirmation" class="form-control @error('confirmation') is-invalid @enderror" required>
                @error('confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger">Delete permanently</button>
            </div>
        </form>
    </div>
</div>
@endrole
@endsection
