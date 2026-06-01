@extends('layouts.app')

@section('title', $committee->name ?? __('committees.show_title', ['id' => $committee->id]))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">
            <span class="badge bg-{{ $committee->type === 'local' ? 'primary' : 'info' }}">
                {{ \App\Support\LocaleHelper::enum('committee_type', $committee->type) }}
            </span>
            {{ $committee->name ?? __('committees.show_title', ['id' => $committee->id]) }}
        </h4>
        <div class="text-muted small">
            {{ \App\Support\LocaleHelper::collegeDisplayName($committee->college) }} ·
            {{ \App\Support\LocaleHelper::departmentDisplayName($committee->department) }} ·
            {{ $committee->period?->name }}
        </div>
    </div>
    <a href="{{ route('committees.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> {{ __('common.back') }}</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card table-card h-100">
            <div class="card-header"><h6 class="mb-0">{{ __('committees.members_card') }}</h6></div>
            <ul class="list-group list-group-flush">
                @foreach($committee->members as $m)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>{{ $m->displayName() }}</strong><br>
                                <small class="text-muted">{{ $m->displayEmail() }}</small>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">
                                {{ str_replace('_', ' ', ucfirst($m->member_role)) }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card table-card h-100">
            <div class="card-header">
                <h6 class="mb-0">{{ __('committees.evaluations_card', ['count' => $committee->evaluations->count()]) }}</h6>
            </div>
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('committees.evaluator') }}</th>
                            <th>{{ __('committees.evaluatee') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th class="text-end">{{ __('common.score') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($committee->evaluations as $e)
                        <tr>
                            <td>{{ $e->evaluator?->name }}</td>
                            <td>{{ $e->evaluatee ? \App\Support\LocaleHelper::staffDisplayName($e->evaluatee) : '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $e->status === 'submitted' ? 'success' : 'secondary' }}">
                                    {{ \App\Support\LocaleHelper::enum('evaluation_status', $e->status) }}
                                </span>
                            </td>
                            <td class="text-end">{{ $e->total_score ? number_format((float) $e->total_score, 2) : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
