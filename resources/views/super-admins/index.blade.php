@extends('layouts.app')

@section('title', __('super_admins.title'))

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('super_admins.title') }}</h5>
        <a href="{{ route('super-admins.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> {{ __('super_admins.new') }}
        </a>
    </div>
    <div class="card-body">
        <p class="text-muted small">{{ __('super_admins.intro') }}</p>
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr>
                    <th>{{ __('common.name') }}</th>
                    <th>{{ __('common.email') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($superAdmins as $admin)
                <tr>
                    <td>
                        <strong>{{ $admin->name }}</strong>
                        @if($admin->id === auth()->id())
                            <span class="badge bg-secondary ms-1">{{ __('common.you') }}</span>
                        @endif
                    </td>
                    <td>{{ $admin->email }}</td>
                    <td>
                        <span class="badge bg-{{ $admin->is_active ? 'success-subtle text-success-emphasis' : 'secondary' }}">
                            {{ $admin->is_active ? __('common.active') : __('common.disabled') }}
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('super-admins.edit', $admin) }}" class="btn btn-outline-primary btn-sm" title="{{ __('common.edit') }}">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($admin->id !== auth()->id())
                            <form action="{{ route('super-admins.destroy', $admin) }}" method="POST" class="d-inline"
                                  data-confirm="{{ __('super_admins.confirm_remove') }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" title="{{ __('super_admins.remove_role') }}">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">{{ __('super_admins.empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
