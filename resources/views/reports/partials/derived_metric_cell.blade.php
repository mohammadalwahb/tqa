@php
    $metricData = $row['derived_metrics'][$metricColumn->id] ?? null;
@endphp
@if(!$metricData || ($metricData['value'] === null && empty($metricData['letter_grade'])))
    <span class="text-muted">—</span>
@elseif(!empty($metricData['letter_grade']))
    <strong class="text-primary">{{ $metricData['letter_grade'] }}</strong>
    @if(!empty($metricData['letter_range']))
        <br><small class="text-muted">{{ $metricData['letter_range'] }}</small>
    @endif
@elseif($metricData['value'] !== null)
    <strong>{{ number_format((float) $metricData['value'], 2) }}</strong>
    @if(!empty($metricData['grade_by_academic_title']))
        <br><small class="text-warning" title="{{ __('reports.title_attr_academic') }}">{{ __('reports.no_letter_grade') }}</small>
    @endif
@else
    <span class="text-muted">—</span>
@endif
