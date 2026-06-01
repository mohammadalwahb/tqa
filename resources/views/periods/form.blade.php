@extends('layouts.app')

@section('title', $period->exists ? __('periods.edit') : __('periods.new'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header"><h5 class="mb-0">{{ $period->exists ? __('periods.edit') : __('periods.new') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ $period->exists ? route('periods.update', $period) : route('periods.store') }}">
                    @csrf
                    @if($period->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">{{ __('common.name') }} <span class="text-danger">{{ __('common.required_mark') }}</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name', $period->name ?: (!$period->exists ? __('periods.default_name', ['year' => config('tqa.current_academic_year')]) : '')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('periods.academic_year') }}</label>
                            <input type="text" name="academic_year" class="form-control"
                                   value="{{ old('academic_year', $period->academic_year ?: (!$period->exists ? config('tqa.current_academic_year') : '')) }}" placeholder="{{ __('periods.year_placeholder') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('periods.start_date') }} <span class="text-danger">{{ __('common.required_mark') }}</span></label>
                            <input type="date" name="start_date" class="form-control"
                                   value="{{ old('start_date', $period->start_date?->toDateString()) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('periods.end_date') }} <span class="text-danger">{{ __('common.required_mark') }}</span></label>
                            <input type="date" name="end_date" class="form-control"
                                   value="{{ old('end_date', $period->end_date?->toDateString()) }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('common.description') }}</label>
                            <textarea name="description" rows="2" class="form-control">{{ old('description', $period->description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       id="isActive" {{ old('is_active', $period->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">{{ __('common.active') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary"><i class="bi bi-save"></i> {{ __('common.save') }}</button>
                        <a href="{{ route('periods.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
