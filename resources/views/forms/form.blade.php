@extends('layouts.app')

@section('title', $form->exists ? 'Edit Form' : 'New Form')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header"><h5 class="mb-0">{{ $form->exists ? 'Edit Form' : 'New Form' }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ $form->exists ? route('forms.update', $form) : route('forms.store') }}">
                    @csrf
                    @if($form->exists) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $form->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target</label>
                        <select name="target_type" class="form-select">
                            <option value="staff" @selected(old('target_type', $form->target_type ?? 'staff') === 'staff')>Teaching Staff</option>
                            <option value="head_of_department" @selected(old('target_type', $form->target_type) === 'head_of_department')>Head of Department</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $form->description) }}</textarea>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="isActive" {{ old('is_active', $form->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>

                    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                    <a href="{{ route('forms.index') }}" class="btn btn-light">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
