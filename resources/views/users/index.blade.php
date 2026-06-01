@extends('layouts.app')

@section('title', __('users.title'))

@section('content')
<div class="card table-card">
    <div class="card-header"><h5 class="mb-0">{{ __('users.title') }}</h5></div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr>
                    <th>{{ __('common.name') }}</th>
                    <th>{{ __('common.email') }}</th>
                    <th>{{ __('common.roles') }}</th>
                    <th>{{ __('common.college') }}</th>
                    <th>{{ __('users.linked_staff') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($users as $u)
                <tr>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td>
                        @foreach($u->roles as $r)
                            <span class="badge bg-primary-subtle text-primary-emphasis">{{ \App\Support\LocaleHelper::roleDisplayName($r->name) }}</span>
                        @endforeach
                    </td>
                    <td>{{ \App\Support\LocaleHelper::collegeDisplayName($u->college) ?: '—' }}</td>
                    <td>{{ $u->staffMember ? \App\Support\LocaleHelper::staffDisplayName($u->staffMember) : '—' }}</td>
                    <td>
                        <span class="badge bg-{{ $u->is_active ? 'success-subtle text-success-emphasis' : 'secondary' }}">
                            {{ $u->is_active ? __('common.active') : __('common.disabled') }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('users.edit', $u) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('users.destroy', $u) }}" method="POST" class="d-inline" data-confirm="{{ __('users.confirm_delete') }}">
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
