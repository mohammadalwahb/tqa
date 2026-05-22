@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="card table-card">
    <div class="card-header"><h5 class="mb-0">Users</h5></div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr><th>Name</th><th>Email</th><th>Roles</th><th>College</th><th>Linked Staff</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>
                        @foreach($u->roles as $r)
                            <span class="badge bg-primary-subtle text-primary-emphasis">{{ $r->name }}</span>
                        @endforeach
                    </td>
                    <td>{{ $u->college?->name_en ?? '—' }}</td>
                    <td>{{ $u->staffMember?->full_name_en ?? '—' }}</td>
                    <td>
                        <span class="badge bg-{{ $u->is_active ? 'success-subtle text-success-emphasis' : 'secondary' }}">
                            {{ $u->is_active ? 'Active' : 'Disabled' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('users.edit', $u) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('users.destroy', $u) }}" method="POST" class="d-inline" data-confirm="Delete user?">
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
@endsection
