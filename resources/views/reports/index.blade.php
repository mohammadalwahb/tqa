@extends('layouts.app')

@section('title', __('reports.title'))

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
            <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> {{ __('common.apply') }}</button></div>
            @if($period && $progress && $progress['required'] > 0)
                <div class="col-md-3 ms-auto">
                    <a href="{{ route('reports.export.pdf', ['period_id' => $period->id]) }}" class="btn btn-outline-danger w-100">
                        <i class="bi bi-file-earmark-pdf"></i> {{ __('reports.export_pdf') }}
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('reports.export.excel', ['period_id' => $period->id]) }}" class="btn btn-outline-success w-100">
                        <i class="bi bi-file-earmark-excel"></i> {{ __('reports.export_excel') }}
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-12">
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="mb-3">
                    @if($scopedCollege)
                        {{ __('reports.college_completion', ['college' => \App\Support\LocaleHelper::collegeDisplayName($scopedCollege)]) }}
                    @else
                        {{ __('reports.university_completion') }}
                    @endif
                </h5>
                @if($progress && $progress['required'] > 0)
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ __('reports.completion_of', ['completed' => $progress['completed'], 'required' => $progress['required']]) }}</span>
                        <strong>{{ $progress['percentage'] }}%</strong>
                    </div>
                    <div class="progress" style="height:18px;">
                        <div class="progress-bar bg-success" style="width: {{ $progress['percentage'] }}%;">
                            {{ $progress['percentage'] }}%
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">{{ __('reports.no_evaluations_period') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header">
        <h6 class="mb-0">{{ __('reports.per_staff_completion') }}</h6>
        @if($period)
            <small class="text-muted">{{ __('reports.derived_metrics_in_export') }}</small>
        @endif
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr>
                    <th>{{ __('nav.staff') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th>{{ __('common.college') }}</th>
                    <th class="text-end">{{ __('reports.required') }}</th>
                    <th class="text-end">{{ __('reports.completed') }}</th>
                    <th class="text-end">{{ __('reports.completion_pct') }}</th>
                    <th class="text-end">{{ __('reports.average_score') }}</th>
                    @foreach($reportQuestionColumns as $questionCol)
                        <th class="text-end" title="{{ $questionCol->text }}">
                            {{ \Illuminate\Support\Str::limit($questionCol->text, 35) }}
                        </th>
                    @endforeach
                    <th class="text-end">{{ __('reports.details') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($staffRows as $row)
                <tr>
                    <td>
                        <strong>{{ \App\Support\LocaleHelper::staffDisplayName($row['staff']) }}</strong><br>
                        <small class="text-muted">{{ $row['staff']->email }}</small>
                    </td>
                    <td>{{ \App\Support\LocaleHelper::departmentDisplayName($row['staff']->department) }}</td>
                    <td>{{ \App\Support\LocaleHelper::collegeDisplayName($row['staff']->department?->college) }}</td>
                    <td class="text-end">{{ $row['required'] }}</td>
                    <td class="text-end">{{ $row['completed'] }}</td>
                    <td class="text-end">
                        <span class="badge bg-{{ $row['percentage'] >= 100 ? 'success' : ($row['percentage'] >= 50 ? 'warning text-dark' : 'danger') }}">
                            {{ $row['percentage'] }}%
                        </span>
                    </td>
                    <td class="text-end">{{ $row['average'] !== null ? number_format((float) $row['average'], 2) : '—' }}</td>
                    @foreach($reportQuestionColumns as $questionCol)
                        <td class="text-end">
                            @include('reports.partials.question_report_cell', ['row' => $row, 'questionColumn' => $questionCol])
                        </td>
                    @endforeach
                    <td class="text-end text-nowrap">
                        @if($period && $row['completed'] > 0)
                            <a href="{{ route('reports.staff.export.pdf', ['staff' => $row['staff']->id, 'period_id' => $period->id]) }}"
                               class="btn btn-outline-danger btn-sm" title="{{ __('common.download_pdf') }}">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        @endif
                        <a href="{{ route('reports.staff.details', ['staff' => $row['staff']->id, 'period_id' => $period?->id]) }}"
                           class="btn btn-outline-primary btn-sm" title="{{ __('common.view_details') }}">
                            <i class="bi bi-graph-up"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ 8 + $reportQuestionColumns->count() }}" class="text-muted text-center py-4">{{ __('reports.no_data_period') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
