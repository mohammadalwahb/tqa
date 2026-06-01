@extends('layouts.app')

@section('title', __('periods.title'))

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('periods.title') }}</h5>
        <a href="{{ route('periods.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> {{ __('periods.new') }}</a>
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr>
                    <th>{{ __('common.name') }}</th>
                    <th>{{ __('periods.academic_year') }}</th>
                    <th>{{ __('periods.start') }}</th>
                    <th>{{ __('periods.end') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($periods as $p)
                <tr>
                    <td><strong>{{ $p->name }}</strong></td>
                    <td>{{ $p->academic_year ?? '—' }}</td>
                    <td>{{ $p->start_date->toDateString() }}</td>
                    <td>{{ $p->end_date->toDateString() }}</td>
                    <td>
                        @if($p->isOpen())
                            <span class="badge bg-success">{{ \App\Support\LocaleHelper::enum('period_status', 'open') }}</span>
                        @elseif($p->is_active)
                            <span class="badge bg-warning text-dark">{{ \App\Support\LocaleHelper::enum('period_status', 'scheduled') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ \App\Support\LocaleHelper::enum('period_status', 'closed') }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('periods.edit', $p) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('periods.destroy', $p) }}" method="POST" class="d-inline" data-confirm="{{ __('periods.confirm_delete') }}">
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
