@extends('layouts.app')

@section('title', 'Colleges')

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">Colleges</h5>
        <a href="{{ route('colleges.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> New College
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle datatable">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name (English)</th>
                        <th>Name (Kurdish)</th>
                        <th class="text-end">Departments</th>
                        <th class="text-end">Staff</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($colleges as $c)
                        <tr>
                            <td>{{ $c->code ?? '—' }}</td>
                            <td><strong>{{ $c->name_en }}</strong></td>
                            <td>{{ $c->name_ku ?? '—' }}</td>
                            <td class="text-end">{{ $c->departments_count }}</td>
                            <td class="text-end">{{ $c->staff_members_count }}</td>
                            <td>
                                <span class="badge bg-{{ $c->is_active ? 'success-subtle text-success-emphasis' : 'secondary-subtle text-secondary-emphasis' }}">
                                    {{ $c->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('colleges.edit', $c) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('colleges.destroy', $c) }}" method="POST" class="d-inline"
                                      data-confirm="This college will be soft-deleted.">
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
