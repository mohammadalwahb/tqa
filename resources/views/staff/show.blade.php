@extends('layouts.app')

@section('title', \App\Support\LocaleHelper::staffDisplayName($staff))

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ \App\Support\LocaleHelper::staffDisplayName($staff) }}</h5>
        <a href="{{ route('staff.edit', $staff) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> {{ __('common.edit') }}</a>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">{{ __('staff.full_name_ku') }}</dt><dd class="col-sm-9">{{ $staff->full_name_ku ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('common.email') }}</dt><dd class="col-sm-9">{{ $staff->email }}</dd>
            <dt class="col-sm-3">{{ __('common.college') }}</dt><dd class="col-sm-9">{{ \App\Support\LocaleHelper::collegeDisplayName($staff->college) ?: '—' }}</dd>
            <dt class="col-sm-3">{{ __('common.department') }}</dt><dd class="col-sm-9">{{ \App\Support\LocaleHelper::departmentDisplayName($staff->department) ?: '—' }}</dd>
            <dt class="col-sm-3">{{ __('staff.gender') }}</dt>
            <dd class="col-sm-9">
                @if($staff->gender === 'male'){{ __('common.male') }}
                @elseif($staff->gender === 'female'){{ __('common.female') }}
                @else — @endif
            </dd>
            <dt class="col-sm-3">{{ __('staff.date_of_birth') }}</dt><dd class="col-sm-9">{{ $staff->date_of_birth?->toDateString() ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('staff.age') }}</dt><dd class="col-sm-9">{{ $staff->age ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('staff.employee_type') }}</dt><dd class="col-sm-9">{{ $staff->employee_type ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('fields.qualification') }}</dt><dd class="col-sm-9">{{ $staff->qualification ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('fields.academic_title') }}</dt><dd class="col-sm-9">{{ $staff->academic_title ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('fields.position') }}</dt><dd class="col-sm-9">{{ $staff->position ?? '—' }}</dd>
            <dt class="col-sm-3">{{ __('common.status') }}</dt>
            <dd class="col-sm-9">
                @if($staff->status)
                    <span class="badge bg-{{ $staff->status->color }}">{{ $staff->status->name }}</span>
                @else — @endif
            </dd>
            <dt class="col-sm-3">{{ __('staff.linked_user') }}</dt>
            <dd class="col-sm-9">{{ $staff->user?->name ?? __('staff.not_linked') }}</dd>
        </dl>
        <a href="{{ route('staff.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> {{ __('common.back') }}</a>
    </div>
</div>
@endsection
