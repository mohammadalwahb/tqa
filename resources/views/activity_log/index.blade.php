@extends('layouts.app')

@section('title', __('activity_log.title'))

@section('content')
<div class="card table-card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('activity_log.advanced_search') }}</h5>
        @if(collect($filters)->filter()->isNotEmpty())
            <a href="{{ route('activity-log.index') }}" class="btn btn-sm btn-outline-secondary">
                {{ __('activity_log.clear_filters') }}
            </a>
        @endif
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="activityQ">{{ __('activity_log.search_text') }}</label>
                <input type="text" name="q" id="activityQ" class="form-control form-control-sm"
                       value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('activity_log.search_text_hint') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="activitySubjectQ">{{ __('activity_log.search_subject') }}</label>
                <input type="text" name="subject_q" id="activitySubjectQ" class="form-control form-control-sm"
                       value="{{ $filters['subject_q'] ?? '' }}" placeholder="{{ __('activity_log.search_subject_hint') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="activityCauserQ">{{ __('activity_log.search_causer') }}</label>
                <input type="text" name="causer_q" id="activityCauserQ" class="form-control form-control-sm"
                       value="{{ $filters['causer_q'] ?? '' }}" placeholder="{{ __('activity_log.search_causer_hint') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="activityEvent">{{ __('common.event') }}</label>
                <select name="event" id="activityEvent" class="form-select form-select-sm">
                    <option value="">{{ __('activity_log.all_events') }}</option>
                    @foreach($events as $event)
                        <option value="{{ $event }}" @selected(($filters['event'] ?? '') === $event)>
                            {{ __('activity_log.event_' . $event) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="activitySubjectType">{{ __('activity_log.record_type') }}</label>
                <select name="subject_type" id="activitySubjectType" class="form-select form-select-sm">
                    <option value="">{{ __('activity_log.all_record_types') }}</option>
                    @foreach($subjectTypes as $type => $label)
                        <option value="{{ $type }}" @selected(($filters['subject_type'] ?? '') === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="activityDateFrom">{{ __('activity_log.date_from') }}</label>
                <input type="date" name="date_from" id="activityDateFrom" class="form-control form-control-sm"
                       value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="activityDateTo">{{ __('activity_log.date_to') }}</label>
                <input type="date" name="date_to" id="activityDateTo" class="form-control form-control-sm"
                       value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-search"></i> {{ __('activity_log.apply_search') }}
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="card-header"><h5 class="mb-0">{{ __('activity_log.recent') }}</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('common.when') }}</th>
                        <th>{{ __('common.user') }}</th>
                        <th>{{ __('common.event') }}</th>
                        <th>{{ __('common.subject') }}</th>
                        <th>{{ __('activity_log.description') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($activities as $a)
                    <tr>
                        <td><small>{{ $a->created_at?->format('Y-m-d H:i') }}</small></td>
                        <td>{{ $a->causer?->name ?? '—' }}</td>
                        <td>
                            @if($a->event)
                                <span class="badge bg-secondary">{{ __('activity_log.event_' . $a->event) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <strong>{{ $a->subject_label ?? '—' }}</strong>
                            @if($a->subject_type)
                                <small class="text-muted d-block">
                                    {{ $subjectTypes[$a->subject_type] ?? class_basename($a->subject_type) }}
                                    #{{ $a->subject_id }}
                                </small>
                            @endif
                        </td>
                        <td>{{ $a->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-muted text-center py-4">{{ __('common.no_matching') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $activities->links() }}
    </div>
</div>
@endsection
