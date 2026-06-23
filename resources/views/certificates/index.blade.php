@extends('layouts.app')

@section('title', __('certificates.my_certificates'))

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('certificates.my_certificates') }}</h5>
        <small class="text-muted">{{ __('certificates.my_certificates_intro') }}</small>
    </div>
    <div class="card-body">
        @forelse($certificates as $template)
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border rounded p-3 mb-2">
                <div>
                    <strong>{{ $template->period->name }}</strong>
                    <div class="text-muted small">{{ __('certificates.available') }}</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('certificates.show', $template->period) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                        <i class="bi bi-image"></i> {{ __('certificates.view') }}
                    </a>
                    <a href="{{ route('certificates.download.pdf', $template->period) }}" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> {{ __('certificates.download_pdf') }}
                    </a>
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">{{ __('certificates.none_available') }}</p>
        @endforelse
    </div>
</div>
@endsection
