@extends('layouts.app')

@section('title', 'Evaluation · ' . $evaluation->evaluatee->full_name_en)

@section('content')
<div class="card table-card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">{{ $evaluation->evaluatee->full_name_en }}</h5>
                <div class="text-muted small">Evaluator: {{ $evaluation->evaluator?->name }}</div>
            </div>
            <div class="text-end">
                <span class="badge bg-{{ $evaluation->status === 'submitted' ? 'success' : 'secondary' }}">
                    {{ ucfirst($evaluation->status) }}
                </span>
                @if($evaluation->total_score !== null)
                    <div class="h3 mb-0">{{ number_format((float) $evaluation->total_score, 2) }} / 5.00</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header"><h6 class="mb-0">Answers</h6></div>
    <div class="card-body p-0">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Category</th><th>Question</th><th>Type</th><th>Answer</th></tr>
            </thead>
            <tbody>
            @foreach($evaluation->answers as $a)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $a->question?->category?->name ?? '—' }}</td>
                    <td>{{ $a->question?->text }}</td>
                    <td><span class="badge bg-light text-muted">{{ ucfirst($a->question?->type) }}</span></td>
                    <td>
                        @if($a->rating_value !== null)
                            <strong>{{ $a->rating_value }}</strong> / 5
                        @elseif($a->number_value !== null)
                            {{ $a->number_value }}
                        @else
                            {{ $a->text_value ?? '—' }}
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <a href="{{ route('evaluations.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    @can('evaluations.manage')
        <a href="{{ route('evaluations.edit', $evaluation) }}" class="btn btn-primary">
            <i class="bi bi-pencil-square"></i> Edit answers
        </a>
    @endcan
</div>
@endsection
