@extends('layouts.app')

@section('title', __('reports.staff_title', ['name' => \App\Support\LocaleHelper::staffDisplayName($staff)]))

@section('content')
<div class="card table-card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1">{{ \App\Support\LocaleHelper::staffDisplayName($staff) }}</h4>
            <div class="text-muted small">{{ $staff->email }}</div>
            <div class="small">
                {{ \App\Support\LocaleHelper::collegeDisplayName($staff->department?->college) }} ·
                {{ \App\Support\LocaleHelper::departmentDisplayName($staff->department) }}
            </div>
        </div>
        <div class="text-end">
            <div class="text-muted small">{{ $period?->name }}</div>
            @if($analytics['overall'] !== null)
                <div class="h2 mb-0">{{ number_format((float) $analytics['overall'], 2) }}</div>
                <div class="text-muted">{{ __('reports.overall_score') }}</div>
            @else
                <div class="text-muted">{{ __('reports.no_score_yet') }}</div>
            @endif
        </div>
    </div>
</div>

@if(!empty($analytics['extractions']))
    <div class="card table-card mb-3">
        <div class="card-header"><h6 class="mb-0">{{ __('reports.derived_metrics') }}</h6></div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($analytics['extractions'] as $metric)
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">{{ $metric['name'] }}</div>
                            <div class="h4 mb-1">
                                @if(!empty($metric['letter_grade']))
                                    <span class="text-primary">{{ $metric['letter_grade'] }}</span>
                                    @if(!empty($metric['letter_range']))
                                        <span class="fs-6 text-muted">({{ $metric['letter_range'] }})</span>
                                    @endif
                                    <span class="fs-6 text-muted d-block">({{ $metric['operation'] }})</span>
                                @elseif($metric['value'] !== null)
                                    {{ number_format((float) $metric['value'], 2) }}
                                    <span class="fs-6 text-muted">({{ $metric['operation'] }})</span>
                                @else
                                    —
                                @endif
                            </div>
                            @if(!empty($metric['grade_by_academic_title']))
                                <div class="small text-muted">{{ __('reports.academic_title_label') }} {{ $staff->academic_title ?? __('reports.academic_title_not_set') }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

@foreach($analytics['by_category'] ?? [] as $category)
    <div class="card table-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0">{{ $category['name'] }}</h6>
            <div class="d-flex gap-2 align-items-center">
                @if(empty($category['include_in_final_score']))
                    <span class="badge bg-warning-subtle text-warning-emphasis">{{ __('reports.not_in_final_score') }}</span>
                @endif
                @if($category['average'] !== null)
                    <span class="badge bg-primary">{{ __('reports.category_avg') }} {{ number_format($category['average'], 2) }}</span>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('evaluations.question') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('reports.visibility') }}</th>
                        <th class="text-end">{{ __('reports.evaluators') }}</th>
                        <th class="text-end">{{ __('reports.question_avg') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($category['questions'] as $q)
                        <tr>
                            <td>{{ $q['text'] }}</td>
                            <td><span class="badge bg-light text-muted">{{ \App\Support\LocaleHelper::enum('question_type', $q['type']) }}</span></td>
                            <td>
                                @if($q['is_shared'])
                                    <span class="badge bg-info-subtle text-info-emphasis">{{ \App\Support\LocaleHelper::enum('visibility', 'shared') }}</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis">{{ \App\Support\LocaleHelper::enum('visibility', 'private') }}</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $q['evaluator_count'] }}</td>
                            <td class="text-end">
                                @if($q['average'] !== null)
                                    <strong>{{ number_format($q['average'], 2) }}</strong>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-3 py-2 small text-muted border-top">
                {{ __('reports.category_score_detail', ['count' => $category['question_count']]) }}
            </div>
        </div>
    </div>
@endforeach

<div class="card table-card">
    <div class="card-header"><h6 class="mb-0">{{ __('reports.all_questions_summary') }}</h6></div>
    <div class="card-body">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('evaluations.question') }}</th>
                    <th>{{ __('evaluations.category') }}</th>
                    <th class="text-end">{{ __('reports.evaluators') }}</th>
                    <th class="text-end">{{ __('reports.average') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($analytics['by_question'] as $q)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $q['text'] }}</td>
                        <td>{{ $q['category'] ?? '—' }}</td>
                        <td class="text-end">{{ $q['count'] }}</td>
                        <td class="text-end">
                            @if($q['average'] !== null)
                                <strong>{{ number_format($q['average'], 2) }}</strong>
                                @if($q['type'] === 'rating')
                                    <div class="progress mt-1" style="height:6px;">
                                        <div class="progress-bar bg-primary" style="width: {{ min(100, ($q['average'] / 5) * 100) }}%;"></div>
                                    </div>
                                @endif
                            @else — @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted text-center py-3">{{ __('reports.no_submitted') }}</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="d-flex gap-2">
            @if($period)
                <a href="{{ route('reports.staff.export.pdf', ['staff' => $staff->id, 'period_id' => $period->id]) }}"
                   class="btn btn-outline-danger">
                    <i class="bi bi-file-earmark-pdf"></i> {{ __('common.download_pdf') }}
                </a>
            @endif
            <a href="{{ route('reports.index', ['period_id' => $period?->id]) }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> {{ __('reports.back_to_reports') }}</a>
        </div>
    </div>
</div>
@endsection
