@extends('layouts.app')

@php
    use App\Support\LocaleHelper;
    $collegeName = $college ? LocaleHelper::collegeDisplayName($college) : '';
@endphp

@section('title', __('committees.create_title'))

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
        {{ __('committees.no_college') }}
    </div>
@else
    <ul class="nav nav-tabs mb-3" id="committeeTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#localTab" type="button">{{ __('committees.tab_local') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#hdTab" type="button">{{ __('committees.tab_hd') }}</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="localTab" role="tabpanel">
            <form method="POST" action="{{ route('committees.store') }}">
                @csrf
                <input type="hidden" name="type" value="local">

                <div class="card table-card mb-3">
                    <div class="card-header"><h6 class="mb-0">{{ __('committees.local_header', ['college' => $collegeName]) }}</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('committees.department') }} <span class="text-danger">*</span></label>
                                <select name="department_id" id="localDept" class="form-select" required>
                                    <option value="">{{ __('common.choose') }}</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}">{{ LocaleHelper::departmentDisplayName($d) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('committees.evaluation_period') }} <span class="text-danger">*</span></label>
                                <select name="evaluation_period_id" class="form-select" required>
                                    @foreach($periods as $p)
                                        <option value="{{ $p->id }}" @selected($period && $period->id == $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('committees.evaluation_form') }}</label>
                                <select name="evaluation_form_id" class="form-select">
                                    <option value="">{{ __('committees.form_default') }}</option>
                                    @foreach($forms->where('target_type', 'staff') as $f)
                                        <option value="{{ $f->id }}">{{ $f->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('committees.committee_name') }}</label>
                                <input type="text" name="name" class="form-control" placeholder="{{ __('common.optional') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card table-card mb-3">
                    <div class="card-header"><h6 class="mb-0">{{ __('committees.members') }}</h6></div>
                    <div class="card-body">
                        <p class="text-muted small">{{ __('committees.local_members_help') }}</p>
                        <p class="text-muted small mb-2"><i class="bi bi-info-circle"></i> {{ __('committees.local_head_auto') }}</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('committees.same_dept_member') }} <span class="text-danger">*</span></label>
                                <select name="same_department_member_id" id="localSameDept" class="staff-member-select" required>
                                    <option disabled value="">{{ __('committees.select_dept_first') }}</option>
                                </select>
                                <small class="text-muted">{{ __('committees.same_dept_hint') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('committees.other_dept_member') }} <span class="text-danger">*</span></label>
                                <select name="other_department_member_id" id="localOtherDept" class="staff-member-select" required>
                                    <option disabled value="">{{ __('committees.select_dept_first') }}</option>
                                </select>
                                <small class="text-muted" id="localOtherDeptHint">{{ __('committees.other_dept_hint', ['college' => $collegeName]) }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary"><i class="bi bi-people"></i> {{ __('committees.create_local') }}</button>
            </form>
        </div>

        <div class="tab-pane fade" id="hdTab" role="tabpanel">
            <form method="POST" action="{{ route('committees.store') }}">
                @csrf
                <input type="hidden" name="type" value="hd">

                <div class="card table-card mb-3">
                    <div class="card-header"><h6 class="mb-0">{{ __('committees.hd_header', ['college' => $collegeName]) }}</h6></div>
                    <div class="card-body">
                        <p class="text-muted small">{{ __('committees.hd_members_help') }}</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('committees.department') }} <span class="text-danger">*</span></label>
                                <select name="department_id" id="hdDept" class="form-select" required>
                                    <option value="">{{ __('common.choose') }}</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}">{{ LocaleHelper::departmentDisplayName($d) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('committees.evaluation_period') }} <span class="text-danger">*</span></label>
                                <select name="evaluation_period_id" class="form-select" required>
                                    @foreach($periods as $p)
                                        <option value="{{ $p->id }}" @selected($period && $period->id == $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('committees.same_dept_member') }} <span class="text-danger">*</span></label>
                                <select name="same_department_member_id" id="hdSameDept" class="staff-member-select" required>
                                    <option disabled value="">{{ __('committees.select_dept_first') }}</option>
                                </select>
                                <small class="text-muted d-block">{{ __('committees.same_dept_hint') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('committees.college_member') }} <span class="text-danger">*</span></label>
                                <select name="other_department_member_id" id="hdOtherDept" class="staff-member-select" required>
                                    <option disabled value="">{{ __('committees.select_dept_first') }}</option>
                                </select>
                                <small class="text-muted d-block" id="hdOtherDeptHint">{{ __('committees.college_member_hint', ['college' => $collegeName]) }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('committees.evaluation_form') }}</label>
                                <select name="evaluation_form_id" class="form-select">
                                    <option value="">{{ __('committees.form_default') }}</option>
                                    @foreach($forms as $f)
                                        <option value="{{ $f->id }}">{{ $f->name }} ({{ $f->target_type }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('committees.committee_name') }}</label>
                                <input type="text" name="name" class="form-control" placeholder="{{ __('common.optional') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary"><i class="bi bi-people"></i> {{ __('committees.create_hd') }}</button>
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    const collegeId = {{ $college?->id ?? 'null' }};
    const staffSelects = {};
    const i18n = {
        noStaff: @json(__('committees.no_staff')),
        searchStaff: @json(__('committees.search_staff')),
        universityWide: @json(__('committees.university_wide_hint')),
        otherDeptHint: @json(__('committees.other_dept_hint', ['college' => $collegeName])),
        collegeMemberHint: @json(__('committees.college_member_hint', ['college' => $collegeName])),
    };

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
            params.set('filter', 'other');
            params.set('exclude_department_id', departmentId);
        }
        const url = "{{ route('committees.staff-options') }}?" + params.toString();
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!res.ok) return { items: [], university_wide: false };
            const data = await res.json();
            if (Array.isArray(data)) {
                return { items: data, university_wide: false };
            }
            return {
                items: data.items ?? [],
                university_wide: Boolean(data.university_wide),
            };
        } catch {
            return { items: [], university_wide: false };
        }
    }

    function initStaffSelect(selectEl, options = {}) {
        if (staffSelects[selectEl.id]) {
            staffSelects[selectEl.id].destroy();
            delete staffSelects[selectEl.id];
        }

        staffSelects[selectEl.id] = new TomSelect(selectEl, {
            maxItems: selectEl.multiple ? 2 : 1,
            maxOptions: null,
            placeholder: options.placeholder || i18n.searchStaff,
            plugins: selectEl.multiple ? ['remove_button'] : [],
            sortField: { field: 'text', direction: 'asc' },
            onItemAdd: function () {
                this.setTextboxValue('');
                this.refreshOptions(false);
            },
        });

        return staffSelects[selectEl.id];
    }

    function refreshStaffSelect(selectEl, payload, emptyMessage) {
        const items = payload.items ?? payload;
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

    function setUniversityWideHint(hintEl, show, defaultHint) {
        if (!hintEl) return;
        hintEl.textContent = show ? i18n.universityWide : (defaultHint || i18n.otherDeptHint);
        hintEl.classList.toggle('text-info', show);
        hintEl.classList.toggle('text-muted', !show);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const localDept = document.getElementById('localDept');
        const sameDept  = document.getElementById('localSameDept');
        const otherDept = document.getElementById('localOtherDept');
        const otherHint = document.getElementById('localOtherDeptHint');

        localDept?.addEventListener('change', async (e) => {
            const id = e.target.value;
            if (!id) return;
            const [samePayload, otherPayload] = await Promise.all([
                fetchStaff(id, 'same'),
                fetchStaff(id, 'other'),
            ]);
            refreshStaffSelect(sameDept, samePayload, i18n.noStaff);
            refreshStaffSelect(otherDept, otherPayload, i18n.noStaff);
            setUniversityWideHint(otherHint, true, i18n.otherDeptHint);
        });

        const hdDept = document.getElementById('hdDept');
        const hdSameDept = document.getElementById('hdSameDept');
        const hdOtherDept = document.getElementById('hdOtherDept');
        const hdOtherHint = document.getElementById('hdOtherDeptHint');
        hdDept?.addEventListener('change', async (e) => {
            const id = e.target.value;
            if (!id) return;
            const [samePayload, collegePayload] = await Promise.all([
                fetchStaff(id, 'same'),
                fetchStaff(id, 'college'),
            ]);
            refreshStaffSelect(hdSameDept, samePayload, i18n.noStaff);
            refreshStaffSelect(hdOtherDept, collegePayload, i18n.noStaff);
            setUniversityWideHint(hdOtherHint, true, i18n.collegeMemberHint);
        });
    });
</script>
@endpush
@endsection
