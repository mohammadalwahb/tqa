@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="card table-card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Evaluation Period</label>
                <select name="period_id" class="form-select">
                    @foreach($periods as $p)
                        <option value="{{ $p->id }}" @selected($period && $period->id == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100"><i class="bi bi-funnel"></i> Apply</button></div>
            @if($period && $progress && $progress['required'] > 0)
                <div class="col-md-3 ms-auto">
                    <a href="{{ route('reports.export.pdf', ['period_id' => $period->id]) }}" class="btn btn-outline-danger w-100">
                        <i class="bi bi-file-earmark-pdf"></i> Export PDF
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('reports.export.excel', ['period_id' => $period->id]) }}" class="btn btn-outline-success w-100">
                        <i class="bi bi-file-earmark-excel"></i> Export Excel
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
                <h5 class="mb-3">University Evaluation Completion</h5>
                @if($progress && $progress['required'] > 0)
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ $progress['completed'] }} of {{ $progress['required'] }} evaluations submitted</span>
                        <strong>{{ $progress['percentage'] }}%</strong>
                    </div>
                    <div class="progress" style="height:18px;">
                        <div class="progress-bar bg-success" style="width: {{ $progress['percentage'] }}%;">
                            {{ $progress['percentage'] }}%
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">No evaluations yet for the selected period.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header">
        <h6 class="mb-0">Per-Staff Evaluation Completion</h6>
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr>
                    <th>Staff</th><th>Department</th><th>College</th>
                    <th class="text-end">Required</th><th class="text-end">Completed</th>
                    <th class="text-end">Completion %</th><th class="text-end">Average Score</th>
                    @foreach($reportQuestionColumns as $questionCol)
                        <th class="text-end" title="{{ $questionCol->text }}">
                            {{ \Illuminate\Support\Str::limit($questionCol->text, 35) }}
                        </th>
                    @endforeach
                    @foreach($derivedMetricColumns as $metricCol)
                        <th class="text-end">{{ $metricCol->name }}</th>
                    @endforeach
                    <th class="text-end">Details</th>
                </tr>
            </thead>
            <tbody>
            @forelse($staffRows as $row)
                <tr>
                    <td>
                        <strong>{{ $row['staff']->full_name_en }}</strong><br>
                        <small class="text-muted">{{ $row['staff']->email }}</small>
                    </td>
                    <td>{{ $row['staff']->department?->name_en }}</td>
                    <td>{{ $row['staff']->department?->college?->name_en }}</td>
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
                    @foreach($derivedMetricColumns as $metricCol)
                        <td class="text-end">
                            @include('reports.partials.derived_metric_cell', ['row' => $row, 'metricColumn' => $metricCol])
                        </td>
                    @endforeach
                    <td class="text-end text-nowrap">
                        @if($period && $row['completed'] > 0)
                            <a href="{{ route('reports.staff.export.pdf', ['staff' => $row['staff']->id, 'period_id' => $period->id]) }}"
                               class="btn btn-outline-danger btn-sm" title="Download PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        @endif
                        <a href="{{ route('reports.staff.details', ['staff' => $row['staff']->id, 'period_id' => $period?->id]) }}"
                           class="btn btn-outline-primary btn-sm" title="View details">
                            <i class="bi bi-graph-up"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ 8 + $reportQuestionColumns->count() + $derivedMetricColumns->count() }}" class="text-muted text-center py-4">No data for this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
