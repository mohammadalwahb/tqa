@extends('layouts.app')

@section('title', __('colleges.title'))

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('colleges.title') }}</h5>
        <a href="{{ route('colleges.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> {{ __('colleges.new') }}
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle datatable">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('common.code') }}</th>
                        <th>{{ __('colleges.name_en') }}</th>
                        <th>{{ __('colleges.name_ku') }}</th>
                        <th class="text-end">{{ __('colleges.departments_count') }}</th>
                        <th class="text-end">{{ __('colleges.staff_count') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($colleges as $c)
                        <tr>
                            <td>{{ $c->code ?? '—' }}</td>
                            <td><strong>{{ $c->name_en }}</strong></td>
                            <td>{{ $c->name_ku ?? '—' }}</td>
                            <td class="text-end">{{ $c->departments_count }}</td>
                            <td class="text-end">{{ $c->staff_members_count }}</td>
                            <td>
                                <span class="badge bg-{{ $c->is_active ? 'success-subtle text-success-emphasis' : 'secondary-subtle text-secondary-emphasis' }}">
                                    {{ $c->is_active ? __('common.active') : __('common.inactive') }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('colleges.edit', $c) }}" class="btn btn-outline-secondary btn-sm" title="{{ __('common.edit') }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('colleges.destroy', $c) }}" method="POST" class="d-inline"
                                      data-confirm="{{ __('colleges.confirm_delete') }}">
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
