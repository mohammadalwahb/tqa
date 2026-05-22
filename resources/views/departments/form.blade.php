@extends('layouts.app')

@section('title', $department->exists ? 'Edit Department' : 'New Department')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header">
                <h5 class="mb-0">{{ $department->exists ? 'Edit Department' : 'New Department' }}</h5>
            </div>
            <div class="card-body">
                <form method="POST"
                      action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}">
                    @csrf
                    @if($department->exists) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">College <span class="text-danger">*</span></label>
                            <select name="college_id" class="form-select @error('college_id') is-invalid @enderror" required>
                                <option value="">— Choose —</option>
                                @foreach($colleges as $c)
                                    <option value="{{ $c->id }}" @selected(old('college_id', $department->college_id) == $c->id)>
                                        {{ $c->name_en }}
                                    </option>
                                @endforeach
                            </select>
                            @error('college_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $department->code) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name (English) <span class="text-danger">*</span></label>
                            <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
                                   value="{{ old('name_en', $department->name_en) }}" required>
                            @error('name_en') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name (Kurdish)</label>
                            <input type="text" name="name_ku" class="form-control"
                                   value="{{ old('name_ku', $department->name_ku) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description', $department->description) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                       id="isActive" {{ old('is_active', $department->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                        <a href="{{ route('departments.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
