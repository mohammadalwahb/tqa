@extends('layouts.app')

@section('title', __('coordinators.title'))

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('coordinators.title') }}</h5>
        <a href="{{ route('coordinators.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> {{ __('coordinators.new') }}
        </a>
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr>
                    <th>{{ __('common.name') }}</th>
                    <th>{{ __('common.email') }}</th>
                    <th>{{ __('common.college') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($coordinators as $c)
                <tr>
                    <td><strong>{{ $c->name }}</strong></td>
                    <td>{{ $c->email }}</td>
                    <td>{{ \App\Support\LocaleHelper::collegeDisplayName($c->college) ?: '—' }}</td>
                    <td>
                        <span class="badge bg-{{ $c->is_active ? 'success-subtle text-success-emphasis' : 'secondary' }}">
                            {{ $c->is_active ? __('common.active') : __('common.disabled') }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('coordinators.edit', $c) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('coordinators.destroy', $c) }}" method="POST" class="d-inline"
                              data-confirm="{{ __('coordinators.confirm_remove') }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('coordinators.empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
