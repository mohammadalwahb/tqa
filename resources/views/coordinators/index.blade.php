@extends('layouts.app')

@section('title', 'Quality College Coordinators')

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">Quality College Coordinators</h5>
        <a href="{{ route('coordinators.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> New Coordinator
        </a>
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr><th>Name</th><th>Email</th><th>College</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            @forelse($coordinators as $c)
                <tr>
                    <td><strong>{{ $c->name }}</strong></td>
                    <td>{{ $c->email }}</td>
                    <td>{{ $c->college?->name_en ?? '—' }}</td>
                    <td>
                        <span class="badge bg-{{ $c->is_active ? 'success-subtle text-success-emphasis' : 'secondary' }}">
                            {{ $c->is_active ? 'Active' : 'Disabled' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('coordinators.edit', $c) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('coordinators.destroy', $c) }}" method="POST" class="d-inline"
                              data-confirm="Remove coordinator role from this user?">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No coordinators yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
