@extends('layouts.app')

@section('title', 'Staff Field Options')

@section('content')
<p class="text-muted small mb-3">
    Configure allowed values for staff forms and CSV import. Inactive values cannot be selected for new staff or imports.
</p>

@canany(['staff_options.manage', 'staff_status.manage'])
<div class="card table-card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Status</h5>
        <a href="{{ route('staff-statuses.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Add
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Color</th>
                        <th class="text-end">Used by</th>
                        <th>Active</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($statuses as $status)
                    <tr>
                        <td><strong>{{ $status->name }}</strong></td>
                        <td><span class="badge bg-{{ $status->color }}">{{ $status->color }}</span></td>
                        <td class="text-end">{{ $status->staff_members_count }}</td>
                        <td>{{ $status->is_active ? 'Yes' : 'No' }}</td>
                        <td class="text-end">
                            <a href="{{ route('staff-statuses.edit', $status) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('staff-statuses.destroy', $status) }}" method="POST" class="d-inline"
                                  data-confirm="Delete this status?">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-muted text-center py-3">No statuses configured yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endcanany

@can('staff_options.manage')
@foreach($fields as $field)
    @php $options = $grouped[$field->value] ?? collect(); @endphp
    <div class="card table-card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ $field->label() }}</h5>
            <a href="{{ route('staff-options.create', ['field' => $field->value]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Add
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Active</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($options as $option)
                        <tr>
                            <td><strong>{{ $option->name }}</strong></td>
                            <td>{{ $option->is_active ? 'Yes' : 'No' }}</td>
                            <td class="text-end">
                                <a href="{{ route('staff-options.edit', $option) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('staff-options.destroy', $option) }}" method="POST" class="d-inline"
                                      data-confirm="Delete this option?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted text-center py-3">No values configured yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach
@endcan
@endsection
