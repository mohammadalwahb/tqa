@php use App\Support\LocaleHelper; @endphp
@extends('layouts.app')

@section('title', $department->exists ? __('departments.edit') : __('departments.new'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header">
                <h5 class="mb-0">{{ $department->exists ? __('departments.edit') : __('departments.new') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST"
                      action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}">
                    @csrf
                    @if($department->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('common.college') }} <span class="text-danger">*</span></label>
                            <select name="college_id" class="form-select @error('college_id') is-invalid @enderror" required>
                                <option value="">{{ __('common.choose') }}</option>
                                @foreach($colleges as $c)
                                    <option value="{{ $c->id }}" @selected(old('college_id', $department->college_id) == $c->id)>
                                        {{ LocaleHelper::collegeDisplayName($c) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('college_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('common.code') }}</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $department->code) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('departments.name_en') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
                                   value="{{ old('name_en', $department->name_en) }}" required>
                            @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('departments.name_ku') }}</label>
                            <input type="text" name="name_ku" class="form-control"
                                   value="{{ old('name_ku', $department->name_ku) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('common.description') }}</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description', $department->description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       id="isActive" {{ old('is_active', $department->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">{{ __('common.active') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> {{ __('common.save') }}</button>
                        <a href="{{ route('departments.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
