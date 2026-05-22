@extends('layouts.app')

@section('title', 'Departments')

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">Departments</h5>
        <a href="{{ route('departments.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> New Department
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle datatable">
                <thead class="table-light">
                    <tr>
                        <th>College</th>
                        <th>Name (English)</th>
                        <th>Name (Kurdish)</th>
                        <th class="text-end">Staff</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $d)
                        <tr>
                            <td>{{ $d->college?->name_en }}</td>
                            <td><strong>{{ $d->name_en }}</strong></td>
                            <td>{{ $d->name_ku ?? '—' }}</td>
                            <td class="text-end">{{ $d->staff_members_count }}</td>
                            <td>
                                <span class="badge bg-{{ $d->is_active ? 'success-subtle text-success-emphasis' : 'secondary-subtle text-secondary-emphasis' }}">
                                    {{ $d->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('departments.edit', $d) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('departments.destroy', $d) }}" method="POST" class="d-inline"
                                      data-confirm="Soft-delete this department?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
