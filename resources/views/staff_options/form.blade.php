@extends('layouts.app')

@section('title', $option->exists ? 'Edit Staff Field Option' : 'New Staff Field Option')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card table-card">
            <div class="card-header">
                <h5 class="mb-0">{{ $option->exists ? 'Edit option' : 'New option' }}</h5>
            </div>
            <div class="card-body">
                <form method="POST"
                      action="{{ $option->exists ? route('staff-options.update', $option) : route('staff-options.store') }}">
                    @csrf
                    @if($option->exists) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label">Field <span class="text-danger">*</span></label>
                        <select name="field" class="form-select @error('field') is-invalid @enderror"
                                {{ $option->exists ? 'disabled' : '' }} required>
                            @foreach($fields as $field)
                                <option value="{{ $field->value }}"
                                    @selected(old('field', $option->field?->value) === $field->value)>
                                    {{ $field->label() }}
                                </option>
                            @endforeach
                        </select>
                        @if($option->exists)
                            <input type="hidden" name="field" value="{{ $option->field?->value }}">
                        @endif
                        @error('field') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $option->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="isActive" {{ old('is_active', $option->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Active (available for new staff & import)</label>
                    </div>

                    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                    <a href="{{ route('staff-options.index') }}" class="btn btn-light">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
