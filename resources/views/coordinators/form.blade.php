@extends('layouts.app')

@section('title', $coordinator->exists ? 'Edit Coordinator' : 'New Coordinator')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header"><h5 class="mb-0">{{ $coordinator->exists ? 'Edit Coordinator' : 'New Coordinator' }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ $coordinator->exists ? route('coordinators.update', $coordinator) : route('coordinators.store') }}">
                    @csrf
                    @if($coordinator->exists) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $coordinator->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email (Google) <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $coordinator->email) }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">User signs in via Google with this email.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">College <span class="text-danger">*</span></label>
                        <select name="college_id" class="form-select @error('college_id') is-invalid @enderror" required>
                            <option value="">— Choose —</option>
                            @foreach($colleges as $c)
                                <option value="{{ $c->id }}" @selected(old('college_id', $coordinator->college_id) == $c->id)>{{ $c->name_en }}</option>
                            @endforeach
                        </select>
                        @error('college_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="isActive" {{ old('is_active', $coordinator->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>

                    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                    <a href="{{ route('coordinators.index') }}" class="btn btn-light">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
