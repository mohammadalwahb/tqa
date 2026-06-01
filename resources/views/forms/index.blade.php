@extends('layouts.app')

@section('title', __('forms.title'))

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('forms.title') }}</h5>
        <a href="{{ route('forms.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> {{ __('forms.new') }}</a>
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr>
                    <th>{{ __('common.name') }}</th>
                    <th>{{ __('common.target') }}</th>
                    <th class="text-end">{{ __('forms.categories_count') }}</th>
                    <th class="text-end">{{ __('forms.questions_count') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($forms as $f)
                <tr>
                    <td><strong><a href="{{ route('forms.show', $f) }}">{{ $f->name }}</a></strong>
                        @if($f->description)<br><small class="text-muted">{{ $f->description }}</small>@endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $f->target_type === 'head_of_department' ? 'info-subtle text-info-emphasis' : 'primary-subtle text-primary-emphasis' }}">
                            {{ \App\Support\LocaleHelper::enum('form_target', $f->target_type) }}
                        </span>
                    </td>
                    <td class="text-end">{{ $f->categories_count }}</td>
                    <td class="text-end">{{ $f->questions_count }}</td>
                    <td>
                        <span class="badge bg-{{ $f->is_active ? 'success-subtle text-success-emphasis' : 'secondary' }}">
                            {{ $f->is_active ? __('common.active') : __('common.disabled') }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('forms.show', $f) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list-check"></i></a>
                        <a href="{{ route('forms.edit', $f) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('forms.destroy', $f) }}" method="POST" class="d-inline" data-confirm="{{ __('forms.confirm_delete') }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
