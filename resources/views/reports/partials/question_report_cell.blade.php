@php
    $data = $row['question_values'][$questionColumn->id] ?? null;
@endphp
@if(!$data)
    <span class="text-muted">—</span>
@elseif($data['type'] === 'text')
    @if($data['text'])
        <small>{{ $data['text'] }}</small>
    @else
        <span class="text-muted">—</span>
    @endif
@elseif($data['average'] !== null)
    <strong>{{ number_format((float) $data['average'], 2) }}</strong>
    @if($questionColumn->type === 'rating')
        <span class="text-muted">{{ __('evaluations.out_of_five') }}</span>
    @endif
@else
    <span class="text-muted">—</span>
@endif
