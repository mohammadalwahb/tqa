@extends('layouts.app')

@section('title', 'Super Admins')

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">Super Admins</h5>
        <a href="{{ route('super-admins.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> New Super Admin
        </a>
    </div>
    <div class="card-body">
        <p class="text-muted small">
            Super Admins sign in with Google using their university email. No password is required.
        </p>
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr><th>Name</th><th>Email</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            @forelse($superAdmins as $admin)
                <tr>
                    <td>
                        <strong>{{ $admin->name }}</strong>
                        @if($admin->id === auth()->id())
                            <span class="badge bg-secondary ms-1">You</span>
                        @endif
                    </td>
                    <td>{{ $admin->email }}</td>
                    <td>
                        <span class="badge bg-{{ $admin->is_active ? 'success-subtle text-success-emphasis' : 'secondary' }}">
                            {{ $admin->is_active ? 'Active' : 'Disabled' }}
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('super-admins.edit', $admin) }}" class="btn btn-outline-primary btn-sm" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($admin->id !== auth()->id())
                            <form action="{{ route('super-admins.destroy', $admin) }}" method="POST" class="d-inline"
                                  data-confirm="Remove Super Admin role from this user?">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" title="Remove role">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No Super Admins yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
