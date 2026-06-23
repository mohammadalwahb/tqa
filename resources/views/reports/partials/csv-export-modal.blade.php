@if(auth()->user()?->isSuperAdmin() && $period && !empty($csvColumns))
<div class="modal fade" id="csvExportModal" tabindex="-1" aria-labelledby="csvExportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('reports.export.csv') }}" id="csvExportForm">
                @csrf
                <input type="hidden" name="period_id" value="{{ $period->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="csvExportModalLabel">{{ __('reports.custom_csv_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">{{ __('reports.custom_csv_help') }}</p>
                    <div id="csvColumnList" class="list-group">
                        @foreach($csvColumns as $column)
                            <div class="list-group-item d-flex align-items-center gap-2 csv-column-item" draggable="true">
                                <span class="text-muted drag-handle" title="{{ __('reports.drag_to_reorder') }}"><i class="bi bi-grip-vertical"></i></span>
                                <input class="form-check-input m-0" type="checkbox" value="{{ $column['key'] }}" checked>
                                <span class="flex-grow-1">{{ $column['label'] }}</span>
                                <button type="button" class="btn btn-sm btn-light move-up" title="{{ __('reports.move_up') }}"><i class="bi bi-arrow-up"></i></button>
                                <button type="button" class="btn btn-sm btn-light move-down" title="{{ __('reports.move_down') }}"><i class="bi bi-arrow-down"></i></button>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-filetype-csv"></i> {{ __('reports.download_csv') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('csvColumnList');
    const form = document.getElementById('csvExportForm');
    if (!list || !form) return;

    let dragItem = null;

    list.querySelectorAll('.csv-column-item').forEach(function (item) {
        item.addEventListener('dragstart', function () {
            dragItem = item;
            item.classList.add('opacity-50');
        });
        item.addEventListener('dragend', function () {
            item.classList.remove('opacity-50');
            dragItem = null;
        });
        item.addEventListener('dragover', function (e) {
            e.preventDefault();
        });
        item.addEventListener('drop', function (e) {
            e.preventDefault();
            if (!dragItem || dragItem === item) return;
            const items = [...list.querySelectorAll('.csv-column-item')];
            const dragIndex = items.indexOf(dragItem);
            const targetIndex = items.indexOf(item);
            if (dragIndex < targetIndex) {
                item.after(dragItem);
            } else {
                item.before(dragItem);
            }
        });
    });

    list.addEventListener('click', function (e) {
        const item = e.target.closest('.csv-column-item');
        if (!item) return;
        if (e.target.closest('.move-up')) {
            item.previousElementSibling?.before(item);
        }
        if (e.target.closest('.move-down')) {
            item.nextElementSibling?.after(item);
        }
    });

    form.addEventListener('submit', function () {
        form.querySelectorAll('input[name="columns[]"]').forEach(function (input) {
            input.remove();
        });
        list.querySelectorAll('.csv-column-item').forEach(function (item) {
            const checkbox = item.querySelector('input[type="checkbox"]');
            if (!checkbox?.checked) return;
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'columns[]';
            hidden.value = checkbox.value;
            form.appendChild(hidden);
        });
    });
});
</script>
@endpush
@endif
