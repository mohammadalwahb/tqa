@extends('layouts.app')

@section('title', __('master_data.title'))

@section('content')
<p class="text-muted mb-4">
    {{ __('master_data.intro') }}
    {{ __('master_data.import_updates') }}
</p>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card table-card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('master_data.colleges') }}</h5></div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('master-data.export.colleges') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download"></i> {{ __('common.export_csv') }}
                </a>
                <form action="{{ route('master-data.import.colleges') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".csv,.txt" required>
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload"></i> {{ __('common.import_csv') }}</button>
                </form>
                <p class="small text-muted mb-0">{{ __('master_data.colleges_columns') }}</p>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card table-card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('master_data.departments') }}</h5></div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('master-data.export.departments') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download"></i> {{ __('common.export_csv') }}
                </a>
                <form action="{{ route('master-data.import.departments') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".csv,.txt" required>
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload"></i> {{ __('common.import_csv') }}</button>
                </form>
                <p class="small text-muted mb-0">{{ __('master_data.departments_columns') }}</p>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card table-card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('master_data.staff_field_options') }}</h5></div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('master-data.export.staff-field-options') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download"></i> {{ __('common.export_csv') }}
                </a>
                <form action="{{ route('master-data.import.staff-field-options') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".csv,.txt" required>
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload"></i> {{ __('common.import_csv') }}</button>
                </form>
                <p class="small text-muted mb-0">{{ __('master_data.options_columns') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
