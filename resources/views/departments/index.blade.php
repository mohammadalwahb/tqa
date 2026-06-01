@php use App\Support\LocaleHelper; @endphp
@extends('layouts.app')

@section('title', __('departments.title'))

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('departments.title') }}</h5>
        <a href="{{ route('departments.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> {{ __('departments.new') }}
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle datatable">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('common.college') }}</th>
                        <th>{{ __('departments.name_en') }}</th>
                        <th>{{ __('departments.name_ku') }}</th>
                        <th class="text-end">{{ __('departments.staff_count') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $d)
                        <tr>
                            <td>{{ LocaleHelper::collegeDisplayName($d->college) }}</td>
                            <td><strong>{{ LocaleHelper::departmentDisplayName($d) }}</strong></td>
                            <td>{{ $d->name_ku ?? '—' }}</td>
                            <td class="text-end">{{ $d->staff_members_count }}</td>
                            <td>
                                <span class="badge bg-{{ $d->is_active ? 'success-subtle text-success-emphasis' : 'secondary-subtle text-secondary-emphasis' }}">
                                    {{ $d->is_active ? __('common.active') : __('common.inactive') }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('departments.edit', $d) }}" class="btn btn-outline-secondary btn-sm" title="{{ __('common.edit') }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('departments.destroy', $d) }}" method="POST" class="d-inline"
                                      data-confirm="{{ __('departments.confirm_delete') }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" title="{{ __('common.delete') }}"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
