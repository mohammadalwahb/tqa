@extends('layouts.app')

@section('title', $college->exists ? __('colleges.edit') : __('colleges.new'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header">
                <h5 class="mb-0">{{ $college->exists ? __('colleges.edit') : __('colleges.new') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ $college->exists ? route('colleges.update', $college) : route('colleges.store') }}">
                    @csrf
                    @if($college->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">{{ __('colleges.name_en') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
                                   value="{{ old('name_en', $college->name_en) }}" required>
                            @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('common.code') }}</label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                   value="{{ old('code', $college->code) }}">
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('colleges.name_ku') }}</label>
                            <input type="text" name="name_ku" class="form-control" value="{{ old('name_ku', $college->name_ku) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('common.description') }}</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description', $college->description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       id="isActive" {{ old('is_active', $college->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">{{ __('common.active') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> {{ __('common.save') }}</button>
                        <a href="{{ route('colleges.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
