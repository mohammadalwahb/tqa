@php use App\Support\LocaleHelper; @endphp
@extends('layouts.app')

@section('title', __('dashboard.title'))

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#3b82f6;"><i class="bi bi-building"></i></div>
                <div>
                    <div class="text-muted small">{{ __('dashboard.colleges') }}</div>
                    <div class="stat-value">{{ $stats['colleges'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#10b981;"><i class="bi bi-diagram-3"></i></div>
                <div>
                    <div class="text-muted small">{{ __('dashboard.departments') }}</div>
                    <div class="stat-value">{{ $stats['departments'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#f59e0b;"><i class="bi bi-person-vcard"></i></div>
                <div>
                    <div class="text-muted small">{{ __('dashboard.active_staff') }}</div>
                    <div class="stat-value">{{ $stats['staff'] }}</div>
                    <div class="small text-muted">{{ __('dashboard.teaching', ['count' => $stats['teaching_staff']]) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#6366f1;"><i class="bi bi-people"></i></div>
                <div>
                    <div class="text-muted small">{{ __('dashboard.committees') }}</div>
                    <div class="stat-value">{{ $stats['committees'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#0ea5e9;"><i class="bi bi-clipboard-data"></i></div>
                <div>
                    <div class="text-muted small">{{ __('dashboard.evaluations_pending') }}</div>
                    <div class="stat-value">{{ $stats['evaluations_open'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#22c55e;"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <div class="text-muted small">{{ __('dashboard.evaluations_submitted') }}</div>
                    <div class="stat-value">{{ $stats['evaluations_done'] }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="text-muted small">{{ __('dashboard.university_progress') }}</div>
                        <div class="h4 mb-0">
                            @if($universityProgress)
                                {{ $universityProgress['percentage'] }}%
                            @else
                                <span class="text-muted">{{ __('common.no_data') }}</span>
                            @endif
                        </div>
                    </div>
                    @if($period)
                        <span class="badge bg-primary-subtle text-primary-emphasis">{{ $period->name }}</span>
                    @endif
                </div>
                @if($universityProgress)
                    <div class="progress" style="height:14px;">
                        <div class="progress-bar bg-success"
                             style="width: {{ $universityProgress['percentage'] }}%;">
                            {{ $universityProgress['completed'] }} / {{ $universityProgress['required'] }}
                        </div>
                    </div>
                @else
                    <p class="text-muted small mb-0">{{ __('dashboard.progress_hint') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($myPending->count())
    <div class="card table-card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-clipboard-check me-1"></i> {{ __('dashboard.my_pending') }}</h5>
            <a href="{{ route('evaluations.index') }}" class="btn btn-sm btn-outline-primary">{{ __('common.view_all') }}</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('dashboard.staff') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('nav.committees') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($myPending as $evaluation)
                        <tr>
                            <td>
                                <strong>{{ LocaleHelper::staffDisplayName($evaluation->evaluatee) }}</strong><br>
                                <small class="text-muted">{{ $evaluation->evaluatee->email }}</small>
                            </td>
                            <td>{{ LocaleHelper::departmentDisplayName($evaluation->evaluatee->department) }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ LocaleHelper::enum('committee_type', $evaluation->committee->type) }}</span>
                                {{ $evaluation->committee->name ?? '#' . $evaluation->committee->id }}
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-primary" href="{{ route('evaluations.edit', $evaluation) }}">
                                    <i class="bi bi-pencil"></i> {{ __('dashboard.fill_evaluation') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
