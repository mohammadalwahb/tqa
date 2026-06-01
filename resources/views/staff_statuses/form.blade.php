@extends('layouts.app')

@section('title', $status->exists ? __('staff_status.edit') : __('staff_status.new'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card table-card">
            <div class="card-header"><h5 class="mb-0">{{ $status->exists ? __('staff_status.edit') : __('staff_status.new') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ $status->exists ? route('staff-statuses.update', $status) : route('staff-statuses.store') }}">
                    @csrf
                    @if($status->exists) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label">{{ __('common.name') }} <span class="text-danger">{{ __('common.required_mark') }}</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $status->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('staff_status.color') }}</label>
                        <select name="color" class="form-select">
                            @foreach(['primary','secondary','success','danger','warning','info','dark'] as $c)
                                <option value="{{ $c }}" @selected(old('color', $status->color ?? 'secondary') === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="isActive" {{ old('is_active', $status->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">{{ __('common.active') }}</label>
                    </div>

                    <button class="btn btn-primary"><i class="bi bi-save"></i> {{ __('common.save') }}</button>
                    <a href="{{ route('staff-options.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
