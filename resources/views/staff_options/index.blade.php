@extends('layouts.app')

@section('title', __('staff_options.title'))

@section('content')
<p class="text-muted small mb-3">{{ __('staff_options.intro') }}</p>

@canany(['staff_options.manage', 'staff_status.manage'])
<div class="card table-card mb-4">
    <div class="card-header">
        <h5 class="mb-0">{{ __('staff_options.status_section') }}</h5>
        <a href="{{ route('staff-statuses.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> {{ __('staff_options.add_status') }}
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('common.name') }}</th>
                        <th>{{ __('common.color') }}</th>
                        <th class="text-end">{{ __('common.used_by') }}</th>
                        <th>{{ __('common.active') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($statuses as $status)
                    <tr>
                        <td><strong>{{ $status->name }}</strong></td>
                        <td><span class="badge bg-{{ $status->color }}">{{ $status->color }}</span></td>
                        <td class="text-end">{{ $status->staff_members_count }}</td>
                        <td>{{ $status->is_active ? __('common.yes') : __('common.no') }}</td>
                        <td class="text-end">
                            <a href="{{ route('staff-statuses.edit', $status) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('staff-statuses.destroy', $status) }}" method="POST" class="d-inline"
                                  data-confirm="{{ __('staff_options.confirm_delete_status') }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-muted text-center py-3">{{ __('staff_options.no_statuses') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endcanany

@can('staff_options.manage')
@foreach($fields as $field)
    @php $options = $grouped[$field->value] ?? collect(); @endphp
    <div class="card table-card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ \App\Support\LocaleHelper::staffFieldLabel($field->value) }}</h5>
            <a href="{{ route('staff-options.create', ['field' => $field->value]) }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> {{ __('common.add') }}
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.active') }}</th>
                            <th class="text-end">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($options as $option)
                        <tr>
                            <td><strong>{{ $option->name }}</strong></td>
                            <td>{{ $option->is_active ? __('common.yes') : __('common.no') }}</td>
                            <td class="text-end">
                                <a href="{{ route('staff-options.edit', $option) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('staff-options.destroy', $option) }}" method="POST" class="d-inline"
                                      data-confirm="{{ __('staff_options.confirm_delete_option') }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted text-center py-3">{{ __('staff_options.no_values') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach
@endcan
@endsection
