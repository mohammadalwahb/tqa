@extends('layouts.app')

@section('title', __('certificates.view_staff'))

@section('content')
<div class="card table-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-0">{{ __('certificates.view_staff') }} — {{ $template->period->name }}</h5>
            <small class="text-muted">{{ __('certificates.bulk_help') }}</small>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('certificate-templates.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> {{ __('common.back') }}</a>
            @if($staffRows->isNotEmpty())
                <form method="POST" action="{{ route('certificate-templates.export.pdf-bulk', $template) }}" id="bulkPdfForm" class="d-flex flex-wrap gap-2">
                    @csrf
                    <input type="hidden" name="download_all" value="0" id="bulkDownloadAll">
                    <button type="button" class="btn btn-sm btn-danger" id="bulkDownloadSelectedBtn">
                        <i class="bi bi-file-earmark-pdf"></i> {{ __('certificates.download_selected_pdf') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDownloadAllBtn">
                        <i class="bi bi-files"></i> {{ __('certificates.download_all_pdf') }}
                    </button>
                </form>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap mb-3">
            <div class="d-flex gap-2" data-college-department-filter>
                <select name="college_id" data-college-select class="form-select form-select-sm">
                    <option value="">{{ __('common.all_colleges') }}</option>
                    @foreach($colleges as $college)
                        <option value="{{ $college->id }}" @selected((int) request('college_id') === $college->id)>
                            {{ \App\Support\LocaleHelper::collegeDisplayName($college) }}
                        </option>
                    @endforeach
                </select>
                <select name="department_id" data-department-select class="form-select form-select-sm">
                    <option value="">{{ __('common.all_departments') }}</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" data-college-id="{{ $department->college_id }}"
                            @selected((int) request('department_id') === $department->id)>
                            {{ \App\Support\LocaleHelper::departmentDisplayName($department) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-funnel"></i>
            </button>
            @if(request()->filled('college_id') || request()->filled('department_id'))
                <a href="{{ route('certificate-templates.staff-picker', $template) }}" class="btn btn-sm btn-outline-secondary">
                    {{ __('certificates.clear_filters') }}
                </a>
            @endif
        </form>

        @if($staffRows->isEmpty())
            <p class="text-muted mb-0">
                {{ request()->filled('college_id') || request()->filled('department_id')
                    ? __('common.no_matching')
                    : __('certificates.bulk_no_staff') }}
            </p>
        @else
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="certSelectAll">
                    {{ __('certificates.select_all') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="certDeselectAll">
                    {{ __('certificates.deselect_all') }}
                </button>
            </div>
            <table class="table align-middle datatable">
                <thead class="table-light">
                    <tr>
                        <th style="width:2.5rem;">
                            <input class="form-check-input" type="checkbox" id="certSelectAllToggle" checked aria-label="{{ __('certificates.select_all') }}">
                        </th>
                        <th>{{ __('staff.full_name_en') }}</th>
                        <th>{{ __('common.college') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody id="certStaffTableBody">
                @foreach($staffRows as $row)
                    @php $staff = $row['staff']; @endphp
                    <tr>
                        <td>
                            <input class="form-check-input cert-staff-checkbox" type="checkbox" name="staff_ids[]" value="{{ $staff->id }}" form="bulkPdfForm" checked>
                        </td>
                        <td>{{ $staff->full_name_en }}</td>
                        <td>{{ \App\Support\LocaleHelper::collegeDisplayName($staff->college ?? $staff->department?->college) }}</td>
                        <td>{{ \App\Support\LocaleHelper::departmentDisplayName($staff->department) }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('certificate-templates.preview', [$template, $staff]) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="bi bi-image"></i> {{ __('certificates.view') }}
                            </a>
                            <a href="{{ route('certificate-templates.export.pdf', [$template, $staff]) }}" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

@include('partials.college-department-filter')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('bulkPdfForm');
    const tableBody = document.getElementById('certStaffTableBody');
    if (!form || !tableBody) return;

    const checkboxes = () => tableBody.querySelectorAll('.cert-staff-checkbox');
    const headerToggle = document.getElementById('certSelectAllToggle');

    function setAll(checked) {
        checkboxes().forEach(function (checkbox) {
            checkbox.checked = checked;
        });
        if (headerToggle) {
            headerToggle.checked = checked;
        }
    }

    document.getElementById('certSelectAll')?.addEventListener('click', function () {
        setAll(true);
    });

    document.getElementById('certDeselectAll')?.addEventListener('click', function () {
        setAll(false);
    });

    headerToggle?.addEventListener('change', function () {
        setAll(headerToggle.checked);
    });

    checkboxes().forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const all = Array.from(checkboxes());
            if (headerToggle) {
                headerToggle.checked = all.length > 0 && all.every(function (item) { return item.checked; });
            }
        });
    });

    document.getElementById('bulkDownloadSelectedBtn')?.addEventListener('click', function () {
        const selected = tableBody.querySelectorAll('.cert-staff-checkbox:checked');
        if (selected.length === 0) {
            alert(@json(__('certificates.bulk_no_staff_selected')));
            return;
        }
        document.getElementById('bulkDownloadAll').value = '0';
        form.submit();
    });

    document.getElementById('bulkDownloadAllBtn')?.addEventListener('click', function () {
        document.getElementById('bulkDownloadAll').value = '1';
        form.submit();
    });
});
</script>
@endpush
