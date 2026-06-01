@extends('layouts.app')

@section('title', $option->exists ? __('staff_options.edit_option') : __('staff_options.new_option'))

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card table-card">
            <div class="card-header">
                <h5 class="mb-0">{{ $option->exists ? __('staff_options.edit_option_short') : __('staff_options.new_option_short') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST"
                      action="{{ $option->exists ? route('staff-options.update', $option) : route('staff-options.store') }}">
                    @csrf
                    @if($option->exists) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label">{{ __('staff_options.field') }} <span class="text-danger">{{ __('common.required_mark') }}</span></label>
                        <select name="field" class="form-select @error('field') is-invalid @enderror"
                                {{ $option->exists ? 'disabled' : '' }} required>
                            @foreach($fields as $field)
                                <option value="{{ $field->value }}"
                                    @selected(old('field', $option->field?->value) === $field->value)>
                                    {{ \App\Support\LocaleHelper::staffFieldLabel($field->value) }}
                                </option>
                            @endforeach
                        </select>
                        @if($option->exists)
                            <input type="hidden" name="field" value="{{ $option->field?->value }}">
                        @endif
                        @error('field') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('common.name') }} <span class="text-danger">{{ __('common.required_mark') }}</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $option->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="isActive" {{ old('is_active', $option->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">{{ __('staff_options.active_import') }}</label>
                    </div>

                    <button class="btn btn-primary"><i class="bi bi-save"></i> {{ __('common.save') }}</button>
                    <a href="{{ route('staff-options.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
