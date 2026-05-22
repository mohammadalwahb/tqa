@extends('layouts.app')

@section('title', 'Master Data Import / Export')

@section('content')
<p class="text-muted mb-4">
    Export or import colleges, departments, and staff field options as UTF-8 CSV files.
    Import updates existing rows when names match, and restores soft-deleted rows when applicable.
</p>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card table-card h-100">
            <div class="card-header"><h5 class="mb-0">Colleges</h5></div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('master-data.export.colleges') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download"></i> Export CSV
                </a>
                <form action="{{ route('master-data.import.colleges') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".csv,.txt" required>
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload"></i> Import CSV</button>
                </form>
                <p class="small text-muted mb-0">Columns: name_en, name_ku, code, description, is_active</p>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card table-card h-100">
            <div class="card-header"><h5 class="mb-0">Departments</h5></div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('master-data.export.departments') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download"></i> Export CSV
                </a>
                <form action="{{ route('master-data.import.departments') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".csv,.txt" required>
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload"></i> Import CSV</button>
                </form>
                <p class="small text-muted mb-0">Columns: college_name_en, name_en, name_ku, code, description, is_active</p>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card table-card h-100">
            <div class="card-header"><h5 class="mb-0">Staff field options</h5></div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('master-data.export.staff-field-options') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download"></i> Export CSV
                </a>
                <form action="{{ route('master-data.import.staff-field-options') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file" class="form-control form-control-sm mb-2" accept=".csv,.txt" required>
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload"></i> Import CSV</button>
                </form>
                <p class="small text-muted mb-0">
                    Columns: category, name, color, is_active.<br>
                    Category: <code>status</code>, <code>employee_type</code>, <code>qualification</code>, <code>academic_title</code>, <code>position</code>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
