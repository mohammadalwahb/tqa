@extends('layouts.app')

@section('title', __('certificates.templates_title'))

@section('content')
<div class="card table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">{{ __('certificates.templates_title') }}</h5>
            <small class="text-muted">{{ __('certificates.templates_intro') }}</small>
        </div>
    </div>
    <div class="card-body">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>{{ __('common.evaluation_period') }}</th>
                    <th>{{ __('nav.forms') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($periods as $period)
                @php $template = $templates->get($period->id); @endphp
                <tr>
                    <td><strong>{{ $period->name }}</strong></td>
                    <td>{{ $template?->form?->name ?? '—' }}</td>
                    <td>
                        @if($template)
                            <span class="badge bg-{{ $template->is_published ? 'success' : 'secondary' }}">
                                {{ $template->is_published ? __('certificates.published') : __('certificates.draft') }}
                            </span>
                        @else
                            <span class="badge bg-light text-muted">{{ __('certificates.not_configured') }}</span>
                        @endif
                    </td>
                    <td class="text-end text-nowrap">
                        @if($template)
                            <a href="{{ route('certificate-templates.edit', $template) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil-square"></i> {{ __('common.edit') }}
                            </a>
                            <form method="POST" action="{{ route('certificate-templates.toggle-published', $template) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-{{ $template->is_published ? 'warning' : 'success' }}">
                                    {{ $template->is_published ? __('certificates.unpublish') : __('certificates.publish') }}
                                </button>
                            </form>
                            <a href="{{ route('certificate-templates.staff-picker', $template) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i> {{ __('certificates.view_staff') }}
                            </a>
                        @else
                            <a href="{{ route('certificate-templates.create', ['period_id' => $period->id]) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg"></i> {{ __('certificates.design') }}
                            </a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
