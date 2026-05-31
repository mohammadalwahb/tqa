@extends('layouts.app')

@section('title', 'Create Committee')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<style>
    .ts-wrapper { width: 100%; }
    .ts-wrapper .ts-control { min-height: 38px; }
</style>
@endpush

@section('content')
@if(!$college)
    <div class="alert alert-warning">
        <i class="bi bi-info-circle"></i>
        You don't have a college assigned. Please ask the Super Admin to assign you to a college from the Coordinators page.
    </div>
@else
    <ul class="nav nav-tabs mb-3" id="committeeTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#localTab" type="button">Local Committee</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#hdTab" type="button">HD Committee</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="localTab" role="tabpanel">
            <form method="POST" action="{{ route('committees.store') }}">
                @csrf
                <input type="hidden" name="type" value="local">

                <div class="card table-card mb-3">
                    <div class="card-header"><h6 class="mb-0">Local Committee – {{ $college->name_en }}</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select name="department_id" id="localDept" class="form-select" required>
                                    <option value="">— Choose —</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}">{{ $d->name_en }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Evaluation Period <span class="text-danger">*</span></label>
                                <select name="evaluation_period_id" class="form-select" required>
                                    @foreach($periods as $p)
                                        <option value="{{ $p->id }}" @selected($period && $period->id == $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Evaluation Form</label>
                                <select name="evaluation_form_id" class="form-select">
                                    <option value="">— Default Active —</option>
                                    @foreach($forms->where('target_type', 'staff') as $f)
                                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Committee Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card table-card mb-3">
                    <div class="card-header"><h6 class="mb-0">Members</h6></div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Required: 1 member from the chosen department, 1 from another department. You (the coordinator) are added automatically.
                            Type in the box to search by name.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Same-department member <span class="text-danger">*</span></label>
                                <select name="same_department_member_id" id="localSameDept" class="staff-member-select" required>
                                    <option disabled value="">— Select department first —</option>
                                </select>
                                <small class="text-muted">One staff member from the chosen department.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Other-department member <span class="text-danger">*</span></label>
                                <select name="other_department_member_id" id="localOtherDept" class="staff-member-select" required>
                                    <option disabled value="">— Select department first —</option>
                                </select>
                                <small class="text-muted">Staff member from a different department of {{ $college->name_en }}.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary"><i class="bi bi-people"></i> Create Local Committee</button>
            </form>
        </div>

        <div class="tab-pane fade" id="hdTab" role="tabpanel">
            <form method="POST" action="{{ route('committees.store') }}">
                @csrf
                <input type="hidden" name="type" value="hd">

                <div class="card table-card mb-3">
                    <div class="card-header"><h6 class="mb-0">HD Committee – {{ $college->name_en }}</h6></div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Each Head of Department gets a dedicated committee. Members:
                            Dean of the College (auto), you (the Quality College Coordinator),
                            one same-department staff member, and one other-department member from {{ $college->name_en }}.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select name="department_id" id="hdDept" class="form-select" required>
                                    <option value="">— Choose —</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}">{{ $d->name_en }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Evaluation Period <span class="text-danger">*</span></label>
                                <select name="evaluation_period_id" class="form-select" required>
                                    @foreach($periods as $p)
                                        <option value="{{ $p->id }}" @selected($period && $period->id == $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Same-department member <span class="text-danger">*</span></label>
                                <select name="same_department_member_id" id="hdSameDept" class="staff-member-select" required>
                                    <option disabled value="">— Select department first —</option>
                                </select>
                                <small class="text-muted d-block">One staff member from the chosen department (not the Head).</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Other-department member <span class="text-danger">*</span></label>
                                <select name="other_department_member_id" id="hdOtherDept" class="staff-member-select" required>
                                    <option disabled value="">— Select department first —</option>
                                </select>
                                <small class="text-muted d-block">One staff member from {{ $college->name_en }} (may be from the same department).</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Evaluation Form</label>
                                <select name="evaluation_form_id" class="form-select">
                                    <option value="">— Default Active —</option>
                                    @foreach($forms as $f)
                                        <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->target_type }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Committee Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary"><i class="bi bi-people"></i> Create HD Committee</button>
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    const collegeId = {{ $college?->id ?? 'null' }};
    const staffSelects = {};

    async function fetchStaff(departmentId, mode) {
        const params = new URLSearchParams({ college_id: collegeId });
        if (mode === 'same') {
            params.set('filter', 'department');
            params.set('department_id', departmentId);
            params.set('exclude_head', '1');
        } else if (mode === 'college') {
            params.set('filter', 'college');
            params.set('department_id', departmentId);
            params.set('exclude_head', '1');
        } else {
            params.set('exclude_department_id', departmentId);
        }
        const url = "{{ route('committees.staff-options') }}?" + params.toString();
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!res.ok) return [];
            return await res.json();
        } catch { return []; }
    }

    function initStaffSelect(selectEl, options = {}) {
        if (staffSelects[selectEl.id]) {
            staffSelects[selectEl.id].destroy();
            delete staffSelects[selectEl.id];
        }

        staffSelects[selectEl.id] = new TomSelect(selectEl, {
            maxItems: selectEl.multiple ? 2 : 1,
            maxOptions: null,
            placeholder: options.placeholder || 'Type to search staff…',
            plugins: selectEl.multiple ? ['remove_button'] : [],
            sortField: { field: 'text', direction: 'asc' },
            onItemAdd: function () {
                this.setTextboxValue('');
                this.refreshOptions(false);
            },
        });

        return staffSelects[selectEl.id];
    }

    function refreshStaffSelect(selectEl, items, emptyMessage) {
        const ts = staffSelects[selectEl.id] || initStaffSelect(selectEl, { placeholder: emptyMessage });

        ts.clear(true);
        ts.clearOptions();

        if (!items.length) {
            ts.addOption({ value: '', text: emptyMessage, disabled: true });
            ts.refreshOptions(false);
            return;
        }

        items.forEach((opt) => ts.addOption({ value: String(opt.id), text: opt.label }));
        ts.refreshOptions(false);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const localDept = document.getElementById('localDept');
        const sameDept  = document.getElementById('localSameDept');
        const otherDept = document.getElementById('localOtherDept');

        localDept?.addEventListener('change', async (e) => {
            const id = e.target.value;
            if (!id) return;
            const [same, other] = await Promise.all([
                fetchStaff(id, 'same'),
                fetchStaff(id, 'other'),
            ]);
            refreshStaffSelect(sameDept, same, 'No staff members found.');
            refreshStaffSelect(otherDept, other, 'No staff members found.');
        });

        const hdDept = document.getElementById('hdDept');
        const hdSameDept = document.getElementById('hdSameDept');
        const hdOtherDept = document.getElementById('hdOtherDept');
        hdDept?.addEventListener('change', async (e) => {
            const id = e.target.value;
            if (!id) return;
            const [same, college] = await Promise.all([
                fetchStaff(id, 'same'),
                fetchStaff(id, 'college'),
            ]);
            refreshStaffSelect(hdSameDept, same, 'No staff members found.');
            refreshStaffSelect(hdOtherDept, college, 'No staff members found.');
        });
    });
</script>
@endpush
@endsection
