@extends('layouts.app')

@section('title', __('evaluations.show_title', ['name' => \App\Support\LocaleHelper::staffDisplayName($evaluation->evaluatee)]))

@section('content')
<div class="card table-card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">{{ \App\Support\LocaleHelper::staffDisplayName($evaluation->evaluatee) }}</h5>
                <div class="text-muted small">{{ __('evaluations.evaluator_label') }} {{ $evaluation->evaluator?->name }}</div>
            </div>
            <div class="text-end">
                <span class="badge bg-{{ $evaluation->status === 'submitted' ? 'success' : 'secondary' }}">
                    {{ \App\Support\LocaleHelper::enum('evaluation_status', $evaluation->status) }}
                </span>
                @if($evaluation->total_score !== null)
                    <div class="h3 mb-0">{{ number_format((float) $evaluation->total_score, 2) }} / 5.00</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header"><h6 class="mb-0">{{ __('evaluations.answers') }}</h6></div>
    <div class="card-body p-0">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('evaluations.category') }}</th>
                    <th>{{ __('evaluations.question') }}</th>
                    <th>{{ __('common.type') }}</th>
                    <th>{{ __('evaluations.answer') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($evaluation->answers as $a)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $a->question?->category?->name ?? '—' }}</td>
                    <td>{{ $a->question?->text }}</td>
                    <td><span class="badge bg-light text-muted">{{ \App\Support\LocaleHelper::enum('question_type', $a->question?->type) }}</span></td>
                    <td>
                        @if($a->rating_value !== null)
                            <strong>{{ $a->rating_value }}</strong>{{ __('evaluations.out_of_five') }}
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
    <a href="{{ $returnRoute ?? route('evaluations.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> {{ __('common.back') }}</a>
    @if(!empty($superAdminScope))
        <a href="{{ route('evaluations.edit', ['evaluation' => $evaluation, 'from' => 'super-admin']) }}" class="btn btn-primary">
            <i class="bi bi-pencil-square"></i> {{ __('evaluations.edit_answers') }}
        </a>
    @else
        @can('evaluations.manage')
            <a href="{{ route('evaluations.edit', $evaluation) }}" class="btn btn-primary">
                <i class="bi bi-pencil-square"></i> {{ __('evaluations.edit_answers') }}
            </a>
        @endcan
    @endif
</div>
@endsection
