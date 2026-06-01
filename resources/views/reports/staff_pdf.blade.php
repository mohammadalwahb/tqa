<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('reports.staff_evaluation', ['name' => \App\Support\LocaleHelper::staffDisplayName($staff)]) }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #1f2937; }
        h1 { margin: 0 0 4px; font-size: 16px; color: #1d4ed8; }
        h2 { margin: 14px 0 6px; font-size: 12px; color: #1e3a8a; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        .meta { color: #4b5563; margin-bottom: 10px; line-height: 1.5; }
        .overall { font-size: 14px; font-weight: bold; color: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #e5e7eb; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-size: 9px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .muted { color: #9ca3af; }
        .question { max-width: 180px; }
        .small { font-size: 8px; color: #6b7280; }
        .badge { display: inline-block; padding: 1px 4px; border-radius: 3px; font-size: 8px; background: #e0e7ff; color: #1e3a8a; }
    </style>
</head>
<body>
    <h1>{{ \App\Support\LocaleHelper::staffDisplayName($staff) }}</h1>
    <div class="meta">
        {{ $staff->email }}<br>
        {{ \App\Support\LocaleHelper::collegeDisplayName($staff->department?->college) }} · {{ \App\Support\LocaleHelper::departmentDisplayName($staff->department) }}<br>
        {{ __('reports.period_label') }} <strong>{{ $period->name }}</strong>
        ({{ $period->start_date->toDateString() }} → {{ $period->end_date->toDateString() }})<br>
        {{ __('reports.generated') }} {{ now()->toDateTimeString() }}
        @if($pdfData['overall'] !== null)
            <br><span class="overall">{{ __('reports.overall_score_short') }} {{ number_format((float) $pdfData['overall'], 2) }}</span>
        @endif
    </div>

    @if(!$pdfData['has_data'])
        <p class="muted">{{ __('reports.no_submitted_period') }}</p>
    @else
        @if(count($pdfData['shared_questions']) > 0)
            <h2>{{ __('reports.shared_questions') }}</h2>
            <p class="small">{{ __('reports.shared_help') }}</p>
            <table>
                <thead>
                    <tr>
                        <th class="question">{{ __('evaluations.question') }}</th>
                        @foreach($pdfData['evaluators'] as $evaluator)
                            <th class="center">
                                {{ $evaluator['name'] }}<br>
                                <span class="small">{{ $evaluator['role'] }}</span>
                            </th>
                        @endforeach
                        <th class="right">{{ __('reports.average') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pdfData['shared_questions'] as $row)
                        <tr>
                            <td class="question">
                                {{ $row['text'] }}
                                @if($row['category'])
                                    <br><span class="small">{{ $row['category'] }}</span>
                                @endif
                            </td>
                            @foreach($pdfData['evaluators'] as $evaluator)
                                <td class="center">
                                    @if($row['values'][$evaluator['id']] !== null)
                                        {{ $row['values'][$evaluator['id']] }}
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="right">
                                @if($row['average'] !== null)
                                    <strong>{{ number_format((float) $row['average'], 2) }}</strong>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(count($pdfData['private_questions']) > 0)
            <h2>{{ __('reports.private_questions') }}</h2>
            <p class="small">{{ __('reports.private_help') }}</p>
            <table>
                <thead>
                    <tr>
                        <th class="question">{{ __('evaluations.question') }}</th>
                        @foreach($pdfData['evaluators'] as $evaluator)
                            <th class="center">
                                {{ $evaluator['name'] }}<br>
                                <span class="small">{{ $evaluator['role'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($pdfData['private_questions'] as $row)
                        <tr>
                            <td class="question">
                                {{ $row['text'] }}
                                @if($row['category'])
                                    <br><span class="small">{{ $row['category'] }}</span>
                                @endif
                                <br><span class="badge">{{ \App\Support\LocaleHelper::enum('visibility', 'private') }}</span>
                            </td>
                            @foreach($pdfData['evaluators'] as $evaluator)
                                <td class="center">
                                    @if($row['values'][$evaluator['id']] !== null)
                                        {{ $row['values'][$evaluator['id']] }}
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(count($pdfData['derived_metrics']) > 0)
            <h2>{{ __('reports.derived_metrics') }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('reports.metric') }}</th>
                        <th>{{ __('reports.operation') }}</th>
                        <th class="right">{{ __('reports.result') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pdfData['derived_metrics'] as $metric)
                        <tr>
                            <td>{{ $metric['name'] }}</td>
                            <td>{{ \App\Support\LocaleHelper::enum('metric_operation', $metric['operation']) }}</td>
                            <td class="right">
                                @if(!empty($metric['letter_grade']))
                                    <strong>{{ $metric['letter_grade'] }}</strong>
                                    @if(!empty($metric['letter_range']))
                                        <br><span class="small">{{ $metric['letter_range'] }}</span>
                                    @endif
                                @elseif($metric['value'] !== null)
                                    {{ number_format((float) $metric['value'], 2) }}
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif
</body>
</html>
