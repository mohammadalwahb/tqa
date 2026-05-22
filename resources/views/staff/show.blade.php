@extends('layouts.app')

@section('title', $staff->full_name_en)

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ $staff->full_name_en }}</h5>
        <a href="{{ route('staff.edit', $staff) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Full Name (Kurdish)</dt><dd class="col-sm-9">{{ $staff->full_name_ku ?? '—' }}</dd>
            <dt class="col-sm-3">Email</dt><dd class="col-sm-9">{{ $staff->email }}</dd>
            <dt class="col-sm-3">College</dt><dd class="col-sm-9">{{ $staff->college?->name_en }}</dd>
            <dt class="col-sm-3">Department</dt><dd class="col-sm-9">{{ $staff->department?->name_en }}</dd>
            <dt class="col-sm-3">Gender</dt><dd class="col-sm-9">{{ ucfirst($staff->gender ?? '—') }}</dd>
            <dt class="col-sm-3">Date of Birth</dt><dd class="col-sm-9">{{ $staff->date_of_birth?->toDateString() ?? '—' }}</dd>
            <dt class="col-sm-3">Age</dt><dd class="col-sm-9">{{ $staff->age ?? '—' }}</dd>
            <dt class="col-sm-3">Employee Type</dt><dd class="col-sm-9">{{ $staff->employee_type ?? '—' }}</dd>
            <dt class="col-sm-3">Qualification</dt><dd class="col-sm-9">{{ $staff->qualification ?? '—' }}</dd>
            <dt class="col-sm-3">Academic Title</dt><dd class="col-sm-9">{{ $staff->academic_title ?? '—' }}</dd>
            <dt class="col-sm-3">Position</dt><dd class="col-sm-9">{{ $staff->position ?? '—' }}</dd>
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                @if($staff->status)
                    <span class="badge bg-{{ $staff->status->color }}">{{ $staff->status->name }}</span>
                @else — @endif
            </dd>
            <dt class="col-sm-3">Linked User</dt>
            <dd class="col-sm-9">{{ $staff->user?->name ?? 'Not linked yet (will link automatically on first Google login)' }}</dd>
        </dl>
        <a href="{{ route('staff.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>
@endsection
