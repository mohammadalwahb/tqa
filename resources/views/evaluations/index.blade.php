@extends('layouts.app')

@section('title', __('evaluations.title'))

@section('content')
<div class="card table-card">
    <div class="card-header"><h5 class="mb-0">{{ __('evaluations.title') }}</h5></div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr>
                    <th>{{ __('evaluations.evaluator') }}</th>
                    <th>{{ __('evaluations.evaluatee') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('common.period') }}</th>
                    <th>{{ __('committees.title') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.score') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($evaluations as $e)
                <tr>
                    <td>
                        {{ $e->evaluator?->name }}
                        @if($e->evaluator?->hasRole('Super Admin'))
                            <span class="badge bg-dark ms-1">{{ __('evaluations.super_admin_shared') }}</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $e->evaluatee ? \App\Support\LocaleHelper::staffDisplayName($e->evaluatee) : '—' }}</strong><br>
                        <small class="text-muted">{{ $e->evaluatee?->email }}</small>
                    </td>
                    <td>{{ \App\Support\LocaleHelper::departmentDisplayName($e->evaluatee?->department) }}</td>
                    <td><small>{{ $e->period?->name }}</small></td>
                    <td>
                        <span class="badge bg-{{ $e->committee->type === 'local' ? 'primary' : 'info' }}">
                            {{ \App\Support\LocaleHelper::enum('committee_type', $e->committee->type) }}
                        </span>
                        <small class="text-muted">#{{ $e->committee->id }}</small>
                    </td>
                    <td>
                        <span class="badge bg-{{ $e->status === 'submitted' ? 'success' : 'secondary' }}">
                            {{ \App\Support\LocaleHelper::enum('evaluation_status', $e->status) }}
                        </span>
                    </td>
                    <td class="text-end">{{ $e->total_score ? number_format((float) $e->total_score, 2) : '—' }}</td>
                    <td class="text-end text-nowrap">
                        @if($e->status === 'submitted')
                            <a href="{{ route('evaluations.show', $e) }}" class="btn btn-outline-secondary btn-sm" title="{{ __('common.view') }}"><i class="bi bi-eye"></i></a>
                            @can('evaluations.manage')
                                <a href="{{ route('evaluations.edit', $e) }}" class="btn btn-outline-primary btn-sm" title="{{ __('evaluations.edit_answers') }}">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            @endcan
                        @else
                            @can('update', $e)
                                <a href="{{ route('evaluations.edit', $e) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> {{ __('evaluations.fill') }}</a>
                            @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">{{ __('evaluations.empty') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
