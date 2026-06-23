@if($period && $zipStaffRows->isNotEmpty())
<div class="modal fade" id="zipExportModal" tabindex="-1" aria-labelledby="zipExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('super-admin.evaluations.export.zip') }}" id="zipExportForm">
                @csrf
                <input type="hidden" name="period_id" value="{{ $period->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="zipExportModalLabel">{{ __('super_admin_evaluations.zip_modal_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">{{ __('super_admin_evaluations.zip_modal_help') }}</p>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="zipSelectAll">
                            {{ __('super_admin_evaluations.select_all') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="zipDeselectAll">
                            {{ __('super_admin_evaluations.deselect_all') }}
                        </button>
                    </div>
                    <div class="export-scroll-panel border rounded">
                        <div class="list-group list-group-flush" id="zipStaffList">
                            @foreach($zipStaffRows as $row)
                                @php $staff = $row['staff']; @endphp
                                <label class="list-group-item d-flex align-items-start gap-2 mb-0 zip-staff-item">
                                    <input class="form-check-input mt-1" type="checkbox" name="staff_ids[]" value="{{ $staff->id }}" checked>
                                    <span class="flex-grow-1">
                                        <strong>{{ \App\Support\LocaleHelper::staffDisplayName($staff) }}</strong>
                                        <small class="text-muted d-block">
                                            {{ \App\Support\LocaleHelper::departmentDisplayName($staff->department) }}
                                            · {{ \App\Support\LocaleHelper::collegeDisplayName($staff->department?->college) }}
                                        </small>
                                    </span>
                                    <span class="badge bg-success-subtle text-success-emphasis">
                                        {{ $row['completed'] }}/{{ $row['required'] }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-file-earmark-zip"></i> {{ __('super_admin_evaluations.download_zip') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('zipExportForm');
    const list = document.getElementById('zipStaffList');
    if (!form || !list) return;

    document.getElementById('zipSelectAll')?.addEventListener('click', function () {
        list.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.checked = true;
        });
    });

    document.getElementById('zipDeselectAll')?.addEventListener('click', function () {
        list.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
            checkbox.checked = false;
        });
    });

    form.addEventListener('submit', function (e) {
        const checked = list.querySelectorAll('input[type="checkbox"]:checked');
        if (checked.length === 0) {
            e.preventDefault();
            alert(@json(__('super_admin_evaluations.zip_no_staff_selected')));
        }
    });
});
</script>
@endpush
@endif
