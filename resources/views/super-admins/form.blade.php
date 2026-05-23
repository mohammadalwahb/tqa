@extends('layouts.app')

@section('title', $superAdmin->exists ? 'Edit Super Admin' : 'New Super Admin')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card table-card">
            <div class="card-header"><h5 class="mb-0">{{ $superAdmin->exists ? 'Edit Super Admin' : 'New Super Admin' }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ $superAdmin->exists ? route('super-admins.update', $superAdmin) : route('super-admins.store') }}">
                    @csrf
                    @if($superAdmin->exists) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $superAdmin->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email (Google) <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $superAdmin->email) }}" required
                               @if($superAdmin->exists) readonly @endif>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">
                            @if($superAdmin->exists)
                                Email cannot be changed after creation. User signs in via Google.
                            @else
                                User will sign in via Google with this email (@uoz.edu.krd or @staff.uoz.edu.krd).
                            @endif
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="isActive" {{ old('is_active', $superAdmin->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>

                    <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                    <a href="{{ route('super-admins.index') }}" class="btn btn-light">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
