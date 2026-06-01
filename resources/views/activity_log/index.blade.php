@extends('layouts.app')

@section('title', __('activity_log.title'))

@section('content')
<div class="card table-card">
    <div class="card-header"><h5 class="mb-0">{{ __('activity_log.recent') }}</h5></div>
    <div class="card-body">
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
            @foreach($activities as $a)
                <tr>
                    <td><small>{{ $a->created_at?->format('Y-m-d H:i') }}</small></td>
                    <td>{{ $a->causer?->name ?? '—' }}</td>
                    <td><span class="badge bg-secondary">{{ $a->event ?? '—' }}</span></td>
                    <td><small class="text-muted">{{ class_basename($a->subject_type ?? '') }} #{{ $a->subject_id ?? '' }}</small></td>
                    <td>{{ $a->description }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $activities->links() }}
    </div>
</div>
@endsection
