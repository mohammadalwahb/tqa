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
                    <div id="bulkPdfStaffIds"></div>
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
            <div id="certStaffPicker" data-all-staff-ids='@json($staffRows->pluck('staff.id')->values())'>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="certSelectAll">
                    {{ __('certificates.select_all') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="certDeselectAll">
                    {{ __('certificates.deselect_all') }}
                </button>
            </div>
            <table class="table align-middle datatable" id="certStaffTable">
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
                            <input class="form-check-input cert-staff-checkbox" type="checkbox" value="{{ $staff->id }}" checked>
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
            </div>
        @endif
    </div>
</div>
@endsection

@include('partials.college-department-filter')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('bulkPdfForm');
    const picker = document.getElementById('certStaffPicker');
    const tableBody = document.getElementById('certStaffTableBody');
    if (!form || !picker || !tableBody) return;

    const allStaffIds = JSON.parse(picker.dataset.allStaffIds || '[]').map(function (id) {
        return parseInt(id, 10);
    });
    const selectedIds = new Set(allStaffIds);
    const headerToggle = document.getElementById('certSelectAllToggle');
    const staffIdsContainer = document.getElementById('bulkPdfStaffIds');
    const tableEl = document.getElementById('certStaffTable');

    function syncHeaderToggle() {
        if (!headerToggle) return;
        headerToggle.checked = allStaffIds.length > 0 && allStaffIds.every(function (id) {
            return selectedIds.has(id);
        });
    }

    function syncVisibleCheckboxes() {
        tableBody.querySelectorAll('.cert-staff-checkbox').forEach(function (checkbox) {
            const id = parseInt(checkbox.value, 10);
            checkbox.checked = selectedIds.has(id);
        });
        syncHeaderToggle();
    }

    function setSelectionForAll(checked) {
        if (checked) {
            allStaffIds.forEach(function (id) {
                selectedIds.add(id);
            });
        } else {
            selectedIds.clear();
        }
        syncVisibleCheckboxes();
    }

    function writeSelectedStaffIdsToForm() {
        if (!staffIdsContainer) return;
        staffIdsContainer.innerHTML = '';
        selectedIds.forEach(function (id) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'staff_ids[]';
            input.value = String(id);
            staffIdsContainer.appendChild(input);
        });
    }

    tableBody.addEventListener('change', function (event) {
        const checkbox = event.target;
        if (!checkbox.classList.contains('cert-staff-checkbox')) return;

        const id = parseInt(checkbox.value, 10);
        if (checkbox.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        syncHeaderToggle();
    });

    document.getElementById('certSelectAll')?.addEventListener('click', function () {
        setSelectionForAll(true);
    });

    document.getElementById('certDeselectAll')?.addEventListener('click', function () {
        setSelectionForAll(false);
    });

    headerToggle?.addEventListener('change', function () {
        setSelectionForAll(headerToggle.checked);
    });

    if (tableEl && window.jQuery && jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable(tableEl)) {
        jQuery(tableEl).on('draw.dt', syncVisibleCheckboxes);
    }

    document.getElementById('bulkDownloadSelectedBtn')?.addEventListener('click', function () {
        if (selectedIds.size === 0) {
            alert(@json(__('certificates.bulk_no_staff_selected')));
            return;
        }
        document.getElementById('bulkDownloadAll').value = '0';
        writeSelectedStaffIdsToForm();
        form.submit();
    });

    document.getElementById('bulkDownloadAllBtn')?.addEventListener('click', function () {
        document.getElementById('bulkDownloadAll').value = '1';
        if (staffIdsContainer) {
            staffIdsContainer.innerHTML = '';
        }
        form.submit();
    });
});
</script>
@endpush
