@extends('layouts.app')

@section('title', $period->exists ? 'Edit Period' : 'New Period')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header"><h5 class="mb-0">{{ $period->exists ? 'Edit Period' : 'New Period' }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ $period->exists ? route('periods.update', $period) : route('periods.store') }}">
                    @csrf
                    @if($period->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name', $period->name ?: (!$period->exists ? 'Academic Year ' . config('tqa.current_academic_year') : '')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Academic Year</label>
                            <input type="text" name="academic_year" class="form-control"
                                   value="{{ old('academic_year', $period->academic_year ?: (!$period->exists ? config('tqa.current_academic_year') : '')) }}" placeholder="e.g. 2025-2026">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control"
                                   value="{{ old('start_date', $period->start_date?->toDateString()) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control"
                                   value="{{ old('end_date', $period->end_date?->toDateString()) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="2" class="form-control">{{ old('description', $period->description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       id="isActive" {{ old('is_active', $period->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                        <a href="{{ route('periods.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
