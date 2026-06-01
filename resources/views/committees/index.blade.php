@extends('layouts.app')

@section('title', __('committees.title'))

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('committees.title') }}</h5>
        <a href="{{ route('committees.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> {{ __('committees.create_btn') }}</a>
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr>
                    <th>{{ __('common.type') }}</th>
                    <th>{{ __('common.name') }}</th>
                    <th>{{ __('committees.college_department') }}</th>
                    <th>{{ __('common.period') }}</th>
                    <th class="text-end">{{ __('committees.members_count') }}</th>
                    <th class="text-end">{{ __('committees.evaluations_count') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($committees as $c)
                <tr>
                    <td>
                        <span class="badge bg-{{ $c->type === 'local' ? 'primary' : 'info' }}">
                            {{ \App\Support\LocaleHelper::enum('committee_type', $c->type) }}
                        </span>
                    </td>
                    <td><strong><a href="{{ route('committees.show', $c) }}">{{ $c->name ?? '#' . $c->id }}</a></strong></td>
                    <td>
                        <small class="text-muted">{{ \App\Support\LocaleHelper::collegeDisplayName($c->college) }}</small><br>
                        {{ \App\Support\LocaleHelper::departmentDisplayName($c->department) }}
                    </td>
                    <td><small>{{ $c->period?->name }}</small></td>
                    <td class="text-end">{{ $c->members->count() }}</td>
                    <td class="text-end">{{ $c->evaluations_count }}</td>
                    <td class="text-end">
                        <a href="{{ route('committees.show', $c) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-eye"></i></a>
                        <form action="{{ route('committees.destroy', $c) }}" method="POST" class="d-inline" data-confirm="{{ __('committees.confirm_delete') }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">{{ __('committees.empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
