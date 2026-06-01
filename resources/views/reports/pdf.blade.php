<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('reports.pdf_title') }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; }
        h1 { margin: 0 0 6px; font-size: 18px; color: #1d4ed8; }
        h2 { margin: 14px 0 6px; font-size: 14px; color: #1e3a8a; }
        .meta { color: #4b5563; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-size: 11px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; background: #e0e7ff; color: #1e3a8a; }
        .progress { background: #e5e7eb; border-radius: 4px; overflow: hidden; height: 10px; }
        .bar { background: #16a34a; height: 100%; }
    </style>
</head>
<body>
    <h1>{{ __('reports.pdf_title') }}</h1>
    <div class="meta">
        {{ __('reports.period_label') }} <strong>{{ $period->name }}</strong>
        ({{ $period->start_date->toDateString() }} → {{ $period->end_date->toDateString() }})<br>
        {{ __('reports.generated') }} {{ now()->toDateTimeString() }}
    </div>

    <h2>{{ __('reports.university_completion') }}</h2>
    <div>{{ $progress['completed'] }} / {{ $progress['required'] }} ({{ $progress['percentage'] }}%)</div>
    <div class="progress" style="margin-top:4px;">
        <div class="bar" style="width: {{ $progress['percentage'] }}%;"></div>
    </div>

    <h2>{{ __('reports.per_staff_completion') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('nav.staff') }}</th>
                <th>{{ __('common.department') }}</th>
                <th>{{ __('common.college') }}</th>
                <th class="right">{{ __('reports.required') }}</th>
                <th class="right">{{ __('reports.completed') }}</th>
                <th class="right">{{ __('reports.completion_pct') }}</th>
                <th class="right">{{ __('reports.avg_score') }}</th>
                @foreach($reportQuestionColumns as $questionCol)
                    <th class="right">{{ \Illuminate\Support\Str::limit($questionCol->text, 28) }}</th>
                @endforeach
                @foreach($derivedMetricColumns as $metricCol)
                    <th class="right">{{ $metricCol->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($staffRows as $row)
                <tr>
                    <td>
                        <strong>{{ \App\Support\LocaleHelper::staffDisplayName($row['staff']) }}</strong><br>
                        <small>{{ $row['staff']->email }}</small>
                    </td>
                    <td>{{ \App\Support\LocaleHelper::departmentDisplayName($row['staff']->department) }}</td>
                    <td>{{ \App\Support\LocaleHelper::collegeDisplayName($row['staff']->department?->college) }}</td>
                    <td class="right">{{ $row['required'] }}</td>
                    <td class="right">{{ $row['completed'] }}</td>
                    <td class="right">{{ $row['percentage'] }}%</td>
                    <td class="right">{{ $row['average'] !== null ? number_format((float) $row['average'], 2) : '—' }}</td>
                    @foreach($reportQuestionColumns as $questionCol)
                        <td class="right">
                            @php $q = $row['question_values'][$questionCol->id] ?? null; @endphp
                            @if($q && $q['type'] === 'text')
                                {{ $q['text'] ?? '—' }}
                            @elseif($q && $q['average'] !== null)
                                {{ number_format((float) $q['average'], 2) }}
                            @else
                                —
                            @endif
                        </td>
                    @endforeach
                    @foreach($derivedMetricColumns as $metricCol)
                        <td class="right">
                            @php $m = $row['derived_metrics'][$metricCol->id] ?? null; @endphp
                            @if($m && !empty($m['letter_grade']))
                                <strong>{{ $m['letter_grade'] }}</strong>
                                @if(!empty($m['letter_range']))
                                    <br><small>{{ $m['letter_range'] }}</small>
                                @endif
                            @elseif($m && $m['value'] !== null)
                                {{ number_format((float) $m['value'], 2) }}
                            @else
                                —
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
