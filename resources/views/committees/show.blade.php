@extends('layouts.app')

@section('title', $committee->name ?? 'Committee #' . $committee->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">
            @if($committee->type === 'local')
                <span class="badge bg-primary">Local</span>
            @else
                <span class="badge bg-info">HD</span>
            @endif
            {{ $committee->name ?? 'Committee #' . $committee->id }}
        </h4>
        <div class="text-muted small">
            {{ $committee->college?->name_en }} · {{ $committee->department?->name_en }} ·
            {{ $committee->period?->name }}
        </div>
    </div>
    <a href="{{ route('committees.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card table-card h-100">
            <div class="card-header"><h6 class="mb-0">Members</h6></div>
            <ul class="list-group list-group-flush">
                @foreach($committee->members as $m)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>{{ $m->displayName() }}</strong><br>
                                <small class="text-muted">{{ $m->user?->email ?? $m->staffMember?->email }}</small>
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
                <h6 class="mb-0">Evaluations ({{ $committee->evaluations->count() }})</h6>
            </div>
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Evaluator</th><th>Evaluatee</th><th>Status</th><th class="text-end">Score</th></tr>
                    </thead>
                    <tbody>
                    @foreach($committee->evaluations as $e)
                        <tr>
                            <td>{{ $e->evaluator?->name }}</td>
                            <td>{{ $e->evaluatee?->full_name_en }}</td>
                            <td>
                                @if($e->status === 'submitted')
                                    <span class="badge bg-success">Submitted</span>
                                @else
                                    <span class="badge bg-secondary">Draft</span>
                                @endif
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
