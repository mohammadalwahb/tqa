@extends('layouts.app')

@section('title', __('super_admin_evaluations.title'))

@section('content')
<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">{{ __('common.evaluation_period') }}</label>
                <select name="period_id" class="form-select">
                    @foreach($periods as $p)
                        <option value="{{ $p->id }}" @selected($period && $period->id == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> {{ __('common.apply') }}</button>
            </div>
            @if($period)
                <div class="col-md-3 ms-auto">
                    <a href="{{ route('super-admin.evaluations.export.zip', ['period_id' => $period->id]) }}"
                       class="btn btn-outline-danger w-100">
                        <i class="bi bi-file-earmark-zip"></i> {{ __('super_admin_evaluations.download_all_pdfs') }}
                    </a>
                    <small class="text-muted d-block mt-1">{{ __('super_admin_evaluations.zip_submitted_only') }}</small>
                </div>
            @endif
        </form>
    </div>
</div>

@if($period && ! $period->isOpen())
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        {{ __('super_admin_evaluations.closed_period_notice') }}
    </div>
@endif

<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('super_admin_evaluations.title') }}</h5>
        <small class="text-muted">{{ __('super_admin_evaluations.intro') }}</small>
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr>
                    <th>{{ __('evaluations.evaluatee') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('common.college') }}</th>
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
                        <strong>{{ $e->evaluatee ? \App\Support\LocaleHelper::staffDisplayName($e->evaluatee) : '—' }}</strong><br>
                        <small class="text-muted">{{ $e->evaluatee?->email }}</small>
                    </td>
                    <td>{{ \App\Support\LocaleHelper::departmentDisplayName($e->evaluatee?->department) }}</td>
                    <td>{{ \App\Support\LocaleHelper::collegeDisplayName($e->evaluatee?->department?->college) }}</td>
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
                            <a href="{{ route('evaluations.show', ['evaluation' => $e, 'from' => 'super-admin']) }}" class="btn btn-outline-secondary btn-sm" title="{{ __('common.view') }}">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('evaluations.edit', ['evaluation' => $e, 'from' => 'super-admin']) }}"
                               class="btn btn-outline-primary btn-sm" title="{{ __('evaluations.edit_answers') }}">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        @else
                            @can('update', $e)
                                <a href="{{ route('evaluations.edit', ['evaluation' => $e, 'from' => 'super-admin']) }}"
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-pencil"></i> {{ __('evaluations.fill') }}
                                </a>
                            @endcan
                        @endif
                        @if($period && $e->status === 'submitted' && $e->evaluatee)
                            <a href="{{ route('reports.staff.export.pdf', ['staff' => $e->evaluatee->id, 'period_id' => $period->id]) }}"
                               class="btn btn-outline-danger btn-sm" title="{{ __('common.download_pdf') }}">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">{{ __('super_admin_evaluations.empty') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
