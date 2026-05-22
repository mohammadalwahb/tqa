@extends('layouts.app')

@section('title', 'Evaluations')

@section('content')
<div class="card table-card">
    <div class="card-header"><h5 class="mb-0">Evaluations</h5></div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr><th>Evaluator</th><th>Evaluatee</th><th>Department</th><th>Period</th><th>Committee</th><th>Status</th><th class="text-end">Score</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            @forelse($evaluations as $e)
                <tr>
                    <td>
                        {{ $e->evaluator?->name }}
                        @if($e->evaluator?->hasRole('Super Admin'))
                            <span class="badge bg-dark ms-1" title="Any Super Admin may fill this once">Super Admin (shared)</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $e->evaluatee?->full_name_en }}</strong><br>
                        <small class="text-muted">{{ $e->evaluatee?->email }}</small>
                    </td>
                    <td>{{ $e->evaluatee?->department?->name_en }}</td>
                    <td><small>{{ $e->period?->name }}</small></td>
                    <td>
                        @if($e->committee->type === 'local')
                            <span class="badge bg-primary">Local</span>
                        @else
                            <span class="badge bg-info">HD</span>
                        @endif
                        <small class="text-muted">#{{ $e->committee->id }}</small>
                    </td>
                    <td>
                        @if($e->status === 'submitted')
                            <span class="badge bg-success">Submitted</span>
                        @else
                            <span class="badge bg-secondary">Draft</span>
                        @endif
                    </td>
                    <td class="text-end">{{ $e->total_score ? number_format((float) $e->total_score, 2) : '—' }}</td>
                    <td class="text-end text-nowrap">
                        @if($e->status === 'submitted')
                            <a href="{{ route('evaluations.show', $e) }}" class="btn btn-outline-secondary btn-sm" title="View"><i class="bi bi-eye"></i></a>
                            @can('evaluations.manage')
                                <a href="{{ route('evaluations.edit', $e) }}" class="btn btn-outline-primary btn-sm" title="Edit answers">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            @endcan
                        @else
                            <a href="{{ route('evaluations.edit', $e) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Fill</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No evaluations.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
