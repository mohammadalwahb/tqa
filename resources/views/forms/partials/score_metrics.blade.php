<div class="card table-card mb-3">
    <div class="card-header"><h6 class="mb-0">Derived metrics</h6></div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            Combine question values, assign a custom name, optional letter grades, and choose whether each metric appears on staff reports.
            Enable <strong>Grade by academic title</strong> to define grade bands per academic title (matched to each staff member's title).
        </p>

        <div class="border rounded p-3 mb-3 bg-light">
            <h6 class="small fw-semibold mb-2">Add derived metric</h6>
            <form method="POST" action="{{ route('forms.score-metrics.store', $form) }}" id="newMetricForm" class="metric-form">
                @csrf
                <div class="mb-2">
                    <label class="form-label small mb-1">Name</label>
                    <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Publications score" required>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small mb-1">Calculation</label>
                        <select name="operation" class="form-select form-select-sm">
                            <option value="sum">Sum of question averages</option>
                            <option value="average">Average of question averages</option>
                        </select>
                    </div>
                    <div class="col-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input type="hidden" name="show_in_reports" value="0">
                            <input class="form-check-input" type="checkbox" name="show_in_reports" value="1" id="newMetricShowReports" checked>
                            <label class="form-check-label small" for="newMetricShowReports">Show in reports</label>
                        </div>
                    </div>
                </div>

                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="grade_by_academic_title" value="0">
                    <input class="form-check-input grade-by-title-toggle" type="checkbox" name="grade_by_academic_title" value="1" id="newMetricGradeByTitle">
                    <label class="form-check-label small" for="newMetricGradeByTitle">Grade by academic title</label>
                </div>

                <div class="title-grade-block" data-title-grades @if(!old('grade_by_academic_title')) style="display:none" @endif>
                    <label class="form-label small mb-1">Grades per academic title</label>
                    <div data-title-blocks-container>
                        @if(old('grade_by_academic_title') && old('title_grades'))
                            @foreach(old('title_grades') as $ti => $tg)
                                @include('forms.partials.academic_title_grade_block', [
                                    'titleIndex' => $ti,
                                    'selectedTitle' => $tg['academic_title'] ?? '',
                                    'grades' => $tg['grades'] ?? [],
                                    'academicTitleOptions' => $academicTitleOptions ?? [],
                                ])
                            @endforeach
                        @endif
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mb-3 add-title-block">
                        <i class="bi bi-plus"></i> Add academic title
                    </button>
                </div>

                <div class="custom-grade-block" data-custom-grades @if(old('grade_by_academic_title')) style="display:none" @endif>
                    <label class="form-label small mb-1">Letter grade mapping (same for all staff)</label>
                    <div class="grade-rows mb-2" data-grade-container>
                        @foreach(old('grades', []) as $gi => $g)
                            @include('forms.partials.grade_row', ['index' => $gi, 'grade' => (object) $g])
                        @endforeach
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm add-grade-row"><i class="bi bi-plus"></i> Add grade</button>
                    </div>
                </div>

                <label class="form-label small mb-1">Questions</label>
                <div class="mb-2" style="max-height:120px; overflow-y:auto;">
                    @foreach($form->questions->filter(fn ($q) => in_array($q->type, ['rating', 'number'], true)) as $q)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="question_ids[]" value="{{ $q->id }}" id="new_metric_q{{ $q->id }}">
                            <label class="form-check-label small" for="new_metric_q{{ $q->id }}">{{ \Illuminate\Support\Str::limit($q->text, 55) }}</label>
                        </div>
                    @endforeach
                </div>

                <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-calculator"></i> Add metric</button>
            </form>
        </div>

        @forelse($form->scoreMetrics as $metric)
            <div class="border rounded mb-2">
                <div class="p-2 d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <strong>{{ $metric->name }}</strong>
                        <span class="badge bg-light text-muted">{{ $metric->operation }}</span>
                        @unless($metric->show_in_reports)
                            <span class="badge bg-warning-subtle text-warning-emphasis">Hidden from reports</span>
                        @endunless
                        @if($metric->grade_by_academic_title)
                            <span class="badge bg-info-subtle text-info-emphasis">By academic title</span>
                        @endif
                        <div class="small text-muted">{{ $metric->questions->count() }} question(s)</div>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#editMetric{{ $metric->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('forms.score-metrics.destroy', [$form, $metric]) }}" method="POST" data-confirm="Remove this metric?">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x"></i></button>
                        </form>
                    </div>
                </div>
                @if($metric->grade_by_academic_title && $metric->gradesGroupedByAcademicTitle()->isNotEmpty())
                    <div class="px-2 pb-2 small">
                        @foreach($metric->gradesGroupedByAcademicTitle() as $title => $titleGrades)
                            <div class="mb-1">
                                <span class="text-muted">{{ $title }}:</span>
                                @foreach($titleGrades as $g)
                                    <span class="badge bg-primary-subtle text-primary-emphasis me-1">{{ $g->label }} ({{ $g->rangeLabel() }})</span>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @elseif(!$metric->grade_by_academic_title && $metric->grades->whereNull('academic_title')->isNotEmpty())
                    <div class="px-2 pb-2 small">
                        @foreach($metric->grades->whereNull('academic_title') as $g)
                            <span class="badge bg-primary-subtle text-primary-emphasis me-1">{{ $g->label }} ({{ $g->rangeLabel() }})</span>
                        @endforeach
                    </div>
                @endif
                <div class="collapse" id="editMetric{{ $metric->id }}">
                    <div class="p-3 border-top bg-light">
                        <form method="POST" action="{{ route('forms.score-metrics.update', [$form, $metric]) }}" class="metric-form">
                            @csrf @method('PUT')
                            <div class="mb-2">
                                <label class="form-label small mb-1">Name</label>
                                <input type="text" name="name" class="form-control form-control-sm" value="{{ $metric->name }}" required>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small mb-1">Calculation</label>
                                    <select name="operation" class="form-select form-select-sm">
                                        <option value="sum" @selected($metric->operation === 'sum')>Sum</option>
                                        <option value="average" @selected($metric->operation === 'average')>Average</option>
                                    </select>
                                </div>
                                <div class="col-6 d-flex align-items-end">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="show_in_reports" value="0">
                                        <input class="form-check-input" type="checkbox" name="show_in_reports" value="1"
                                               id="metricShow{{ $metric->id }}" @checked($metric->show_in_reports)>
                                        <label class="form-check-label small" for="metricShow{{ $metric->id }}">Show in reports</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input type="hidden" name="grade_by_academic_title" value="0">
                                <input class="form-check-input grade-by-title-toggle" type="checkbox" name="grade_by_academic_title" value="1"
                                       id="metricGradeByTitle{{ $metric->id }}" @checked($metric->grade_by_academic_title)>
                                <label class="form-check-label small" for="metricGradeByTitle{{ $metric->id }}">Grade by academic title</label>
                            </div>

                            <div class="title-grade-block" data-title-grades @unless($metric->grade_by_academic_title) style="display:none" @endunless>
                                <label class="form-label small mb-1">Grades per academic title</label>
                                <div data-title-blocks-container>
                                    @foreach($metric->gradesGroupedByAcademicTitle() as $ti => $titleGrades)
                                        @include('forms.partials.academic_title_grade_block', [
                                            'titleIndex' => $loop->index,
                                            'selectedTitle' => $ti,
                                            'grades' => $titleGrades,
                                            'academicTitleOptions' => $academicTitleOptions ?? [],
                                        ])
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mb-3 add-title-block">
                                    <i class="bi bi-plus"></i> Add academic title
                                </button>
                            </div>

                            <div class="custom-grade-block" data-custom-grades @if($metric->grade_by_academic_title) style="display:none" @endif>
                                <label class="form-label small mb-1">Letter grade mapping (same for all staff)</label>
                                <div class="grade-rows mb-2" data-grade-container>
                                    @foreach($metric->grades->whereNull('academic_title') as $gi => $g)
                                        @include('forms.partials.grade_row', ['index' => $gi, 'grade' => $g])
                                    @endforeach
                                </div>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-secondary btn-sm add-grade-row"><i class="bi bi-plus"></i> Add grade</button>
                                </div>
                            </div>

                            <label class="form-label small mb-1">Questions</label>
                            <div class="mb-2" style="max-height:120px; overflow-y:auto;">
                                @foreach($form->questions->filter(fn ($q) => in_array($q->type, ['rating', 'number'], true)) as $q)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="question_ids[]" value="{{ $q->id }}"
                                               id="metric{{ $metric->id }}_q{{ $q->id }}"
                                               @checked($metric->questions->contains('id', $q->id))>
                                        <label class="form-check-label small" for="metric{{ $metric->id }}_q{{ $q->id }}">{{ \Illuminate\Support\Str::limit($q->text, 55) }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <button class="btn btn-primary btn-sm"><i class="bi bi-save"></i> Save metric</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted small mb-0">No derived metrics yet.</p>
        @endforelse
    </div>
</div>

<template id="gradeRowTemplate">
    @include('forms.partials.grade_row', ['index' => '__INDEX__', 'grade' => null])
</template>

<template id="titleGradeRowTemplate">
    @include('forms.partials.title_grade_row', ['titleIndex' => '__TITLE_INDEX__', 'gradeIndex' => '__INDEX__', 'grade' => null])
</template>

<template id="titleBlockTemplate">
    @include('forms.partials.academic_title_grade_block', [
        'titleIndex' => '__TITLE_INDEX__',
        'selectedTitle' => '',
        'grades' => [],
        'academicTitleOptions' => $academicTitleOptions ?? [],
    ])
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let gradeIndex = 0;

    function nextGradeIndex() { return gradeIndex++; }

    function maxTitleIndexInContainer(container) {
        let max = -1;
        if (!container) return max;
        container.querySelectorAll('[name^="title_grades["]').forEach((el) => {
            const match = el.name.match(/^title_grades\[(\d+)\]/);
            if (match) {
                max = Math.max(max, parseInt(match[1], 10));
            }
        });
        return max;
    }

    function nextTitleIndexFor(container) {
        return maxTitleIndexInContainer(container) + 1;
    }

    function toggleGradeMode(form) {
        const toggle = form?.querySelector('.grade-by-title-toggle');
        const custom = form?.querySelector('[data-custom-grades]');
        const byTitle = form?.querySelector('[data-title-grades]');
        if (!toggle) return;
        if (custom) custom.style.display = toggle.checked ? 'none' : '';
        if (byTitle) byTitle.style.display = toggle.checked ? '' : 'none';
        if (toggle.checked) {
            const container = byTitle?.querySelector('[data-title-blocks-container]');
            if (container && !container.children.length) {
                container.appendChild(createTitleBlock(nextTitleIndexFor(container)));
            }
        }
    }

    document.querySelectorAll('.metric-form').forEach((form) => {
        const toggle = form.querySelector('.grade-by-title-toggle');
        if (toggle) {
            toggle.addEventListener('change', () => toggleGradeMode(form));
            toggleGradeMode(form);
        }
    });

    function createGradeRow(index, data = {}) {
        const tpl = document.getElementById('gradeRowTemplate');
        const html = tpl.innerHTML.replaceAll('__INDEX__', String(index));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const row = wrap.firstElementChild;
        if (data.label) row.querySelector('[data-grade-label]').value = data.label;
        if (data.min !== undefined && data.min !== '') row.querySelector('[data-grade-min]').value = data.min;
        if (data.max !== undefined) row.querySelector('[data-grade-max]').value = data.max;
        return row;
    }

    function createTitleGradeRow(titleIdx, gradeIdx) {
        const tpl = document.getElementById('titleGradeRowTemplate');
        const html = tpl.innerHTML
            .replaceAll('__TITLE_INDEX__', String(titleIdx))
            .replaceAll('__INDEX__', String(gradeIdx));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        return wrap.firstElementChild;
    }

    function createTitleBlock(titleIdx) {
        const tpl = document.getElementById('titleBlockTemplate');
        const html = tpl.innerHTML.replaceAll('__TITLE_INDEX__', String(titleIdx));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        const block = wrap.firstElementChild;
        const container = block.querySelector('[data-grade-container]');
        if (container && !container.children.length) {
            container.appendChild(createTitleGradeRow(titleIdx, 0));
        }
        return block;
    }

    document.querySelectorAll('[data-grade-container]').forEach((container) => {
        if (!container.closest('[data-title-block]') && !container.children.length) {
            container.appendChild(createGradeRow(nextGradeIndex()));
        }
    });

    document.body.addEventListener('click', (e) => {
        if (e.target.closest('.add-title-block')) {
            const form = e.target.closest('.metric-form');
            const container = form?.querySelector('[data-title-blocks-container]');
            if (container) {
                container.appendChild(createTitleBlock(nextTitleIndexFor(container)));
            }
            return;
        }

        if (e.target.closest('.remove-title-block')) {
            e.preventDefault();
            const block = e.target.closest('[data-title-block]');
            const container = block?.parentElement;
            block?.remove();
            if (container && !container.children.length) {
                container.appendChild(createTitleBlock(nextTitleIndexFor(container)));
            }
            return;
        }

        const addBtn = e.target.closest('.add-grade-row');
        if (addBtn) {
            const titleBlock = addBtn.closest('[data-title-block]');
            if (titleBlock) {
                const select = titleBlock.querySelector('select[name*="[academic_title]"]');
                const match = select?.name.match(/title_grades\[(\d+)\]/);
                const titleIdx = match ? match[1] : nextTitleIndexFor(titleBlock.closest('[data-title-blocks-container]'));
                const container = titleBlock.querySelector('[data-grade-container]');
                const gradeIdx = container?.children.length ?? 0;
                container?.appendChild(createTitleGradeRow(titleIdx, gradeIdx));
            } else {
                const container = addBtn.closest('form')?.querySelector('[data-custom-grades] [data-grade-container]');
                container?.appendChild(createGradeRow(nextGradeIndex()));
            }
            return;
        }

        if (e.target.closest('.remove-grade-row')) {
            e.preventDefault();
            const row = e.target.closest('.grade-row');
            const container = row?.parentElement;
            const titleBlock = row?.closest('[data-title-block]');
            row?.remove();
            if (container && !container.children.length) {
                if (titleBlock) {
                    const select = titleBlock.querySelector('select[name*="[academic_title]"]');
                    const match = select?.name.match(/title_grades\[(\d+)\]/);
                    const titleIdx = match ? match[1] : 0;
                    container.appendChild(createTitleGradeRow(titleIdx, 0));
                } else {
                    container.appendChild(createGradeRow(nextGradeIndex()));
                }
            }
        }
    });
});
</script>
@endpush
