@extends('layouts.app')

@section('title', $template->exists ? __('certificates.edit_template') : __('certificates.design'))

@push('styles')
<style>
.cert-designer { display: grid; grid-template-columns: 280px 1fr; gap: 1rem; align-items: start; }
@media (max-width: 992px) { .cert-designer { grid-template-columns: 1fr; } }
.cert-palette-scroll {
    max-height: min(55vh, 26rem);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}
.cert-group-title {
    font-size: .75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748b;
    margin: .75rem 0 .35rem;
}
.cert-group-title:first-child { margin-top: 0; }
.cert-palette .field-chip {
    cursor: grab;
    user-select: none;
    border: 1px dashed #cbd5e1;
    border-radius: .375rem;
    padding: .45rem .65rem;
    margin-bottom: .4rem;
    background: #fff;
    font-size: .8125rem;
}
.cert-palette .field-chip.metric { border-color: #c4b5fd; background: #faf5ff; }
.cert-canvas-wrap {
    overflow: auto;
    background: #cbd5e1;
    padding: 1rem;
    border-radius: .5rem;
}
.cert-canvas {
    position: relative;
    margin: 0 auto;
    background: #fff center/cover no-repeat;
    box-shadow: 0 4px 24px rgba(0,0,0,.2);
    transform-origin: top left;
}
.cert-guide-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 5;
}
.cert-guide-line {
    position: absolute;
    background: #ef4444;
    opacity: .85;
}
.cert-guide-line.vertical { width: 1px; top: 0; bottom: 0; }
.cert-guide-line.horizontal { height: 1px; left: 0; right: 0; }
.cert-canvas-field {
    position: absolute;
    border: 1px dashed #2563eb;
    background: rgba(37, 99, 235, 0.08);
    cursor: move;
    padding: 2px 4px;
    overflow: hidden;
    line-height: 1.2;
    z-index: 10;
}
.cert-canvas-field.is-text { border-color: #059669; background: rgba(5, 150, 105, 0.08); }
.cert-canvas-field.selected { border-style: solid; background: rgba(37, 99, 235, 0.15); }
.cert-canvas-field.is-text.selected { background: rgba(5, 150, 105, 0.15); }
.cert-canvas-field .remove-field {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: none;
    background: #dc2626;
    color: #fff;
    font-size: 12px;
    line-height: 1;
    display: none;
    z-index: 2;
}
.cert-canvas-field.selected .remove-field { display: block; }
</style>
@endpush

@section('content')
@php
    $groupedCatalog = collect($fieldCatalog)->groupBy('group');
    $groupLabels = [
        'staff' => __('certificates.group_staff'),
        'questions' => __('certificates.group_questions'),
        'metrics' => __('certificates.group_metrics'),
    ];
@endphp
<form method="POST"
      action="{{ $template->exists ? route('certificate-templates.update', $template) : route('certificate-templates.store') }}"
      enctype="multipart/form-data"
      id="certificateDesignerForm">
    @csrf
    @if($template->exists) @method('PUT') @endif

    <input type="hidden" name="evaluation_period_id" value="{{ $period->id }}">
    <input type="hidden" name="layout_json" id="layoutJson" value="">
    <input type="hidden" name="is_published" value="0">
    <input type="hidden" name="is_published" value="1" id="publishFlag" disabled>

    <div class="card table-card mb-3">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">{{ __('common.evaluation_period') }}</label>
                <input type="text" class="form-control" value="{{ $period->name }}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('nav.forms') }}</label>
                <select name="evaluation_form_id" class="form-select" id="formSelect">
                    @foreach($forms as $f)
                        <option value="{{ $f->id }}" @selected($form->id === $f->id)>{{ $f->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">{{ __('certificates.form_change_hint') }}</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('certificates.background_image') }}</label>
                <input type="file" name="background_image" class="form-control" accept="image/*">
            </div>
        </div>
    </div>

    <div class="cert-designer">
        <div class="cert-palette">
            <div class="card table-card">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <strong class="small">{{ __('certificates.available_fields') }}</strong>
                </div>
                <div class="card-body cert-palette-scroll" id="fieldPalette">
                    <button type="button" class="btn btn-sm btn-outline-success w-100 mb-2" id="addTextFieldBtn">
                        <i class="bi bi-fonts"></i> {{ __('certificates.add_text') }}
                    </button>

                    @foreach(['staff', 'questions', 'metrics'] as $groupKey)
                        @if(($groupedCatalog->get($groupKey) ?? collect())->isNotEmpty())
                            <div class="cert-group-title">{{ $groupLabels[$groupKey] ?? $groupKey }}</div>
                            @foreach($groupedCatalog->get($groupKey) as $field)
                                <div class="field-chip {{ $groupKey === 'metrics' ? 'metric' : '' }}"
                                     data-key="{{ $field['key'] }}"
                                     data-label="{{ $field['label'] }}">
                                    <i class="bi bi-plus-circle text-primary"></i> {{ $field['label'] }}
                                </div>
                            @endforeach
                        @endif
                    @endforeach

                    @if(collect($fieldCatalog)->where('group', 'metrics')->isEmpty())
                        <div class="cert-group-title">{{ __('certificates.group_metrics') }}</div>
                        <p class="text-muted small mb-0">{{ __('certificates.no_metrics_hint') }}</p>
                    @endif
                </div>
            </div>

            <div class="card table-card mt-3" id="fieldInspector" style="display:none;">
                <div class="card-header py-2"><strong class="small">{{ __('certificates.field_settings') }}</strong></div>
                <div class="card-body">
                    <div class="mb-2" id="inspectorContentWrap" style="display:none;">
                        <label class="form-label small">{{ __('certificates.text_content') }}</label>
                        <textarea class="form-control form-control-sm" id="inspectorContent" rows="2" maxlength="500"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">{{ __('certificates.font_size') }}</label>
                        <input type="number" class="form-control form-control-sm" id="inspectorFontSize" min="8" max="120" value="20">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">{{ __('certificates.font_weight') }}</label>
                        <select class="form-select form-select-sm" id="inspectorFontWeight">
                            <option value="normal">{{ __('certificates.weight_normal') }}</option>
                            <option value="bold">{{ __('certificates.weight_bold') }}</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">{{ __('certificates.text_color') }}</label>
                        <input type="color" class="form-control form-control-color w-100" id="inspectorColor" value="#000000">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">{{ __('certificates.text_align') }}</label>
                        <select class="form-select form-select-sm" id="inspectorAlign">
                            <option value="left">{{ __('certificates.align_left') }}</option>
                            <option value="center">{{ __('certificates.align_center') }}</option>
                            <option value="right">{{ __('certificates.align_right') }}</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small">{{ __('certificates.field_width') }}</label>
                        <input type="number" class="form-control form-control-sm" id="inspectorWidth" min="40" max="1000" value="300">
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div>
                    <strong>{{ __('certificates.canvas_title') }}</strong>
                    <span class="text-muted small">A4 {{ __('certificates.landscape') }}</span>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="alignmentGuidesToggle" checked>
                        <label class="form-check-label small" for="alignmentGuidesToggle">{{ __('certificates.alignment_guides') }}</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save"></i> {{ __('common.save') }}</button>
                    <button type="button" class="btn btn-success btn-sm" id="saveAndPublishBtn"><i class="bi bi-check-circle"></i> {{ __('certificates.save_publish') }}</button>
                </div>
            </div>
            <div class="cert-canvas-wrap">
                <div class="cert-canvas" id="certCanvas"
                     style="width:{{ \App\Models\CertificateTemplate::CANVAS_WIDTH }}px;height:{{ \App\Models\CertificateTemplate::CANVAS_HEIGHT }}px;@if($template->backgroundUrl())background-image:url('{{ $template->backgroundUrl() }}');@endif">
                    <div class="cert-guide-layer" id="guideLayer"></div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const CANVAS_W = {{ \App\Models\CertificateTemplate::CANVAS_WIDTH }};
    const CANVAS_H = {{ \App\Models\CertificateTemplate::CANVAS_HEIGHT }};
    const SNAP_THRESHOLD = 6;

    const canvas = document.getElementById('certCanvas');
    const guideLayer = document.getElementById('guideLayer');
    const form = document.getElementById('certificateDesignerForm');
    const layoutInput = document.getElementById('layoutJson');
    const inspector = document.getElementById('fieldInspector');
    const inspectorContentWrap = document.getElementById('inspectorContentWrap');
    const alignmentToggle = document.getElementById('alignmentGuidesToggle');
    const scale = Math.min(1, (canvas.parentElement.clientWidth - 32) / CANVAS_W);
    canvas.style.transform = 'scale(' + scale + ')';
    canvas.style.marginBottom = ((CANVAS_H * scale) - CANVAS_H) + 'px';

    let fields = @json($placedFields);
    let selectedEl = null;
    let dragOffset = { x: 0, y: 0 };
    let textCounter = fields.filter(f => (f.key || '').startsWith('text:')).length;

    const sampleValues = {
        full_name_en: @json(__('certificates.sample_name')),
        college: @json(__('certificates.sample_college')),
        department: @json(__('certificates.sample_department')),
        academic_title: @json(__('certificates.sample_title')),
    };

    function isTextField(field) {
        return (field.key || '').startsWith('text:');
    }

    function defaultField(key, label) {
        return {
            key: key,
            label: label,
            x: 80,
            y: 80,
            width: 320,
            font_size: 22,
            font_weight: 'bold',
            color: '#000000',
            text_align: 'center',
        };
    }

    function defaultTextField(content) {
        textCounter += 1;
        return Object.assign(defaultField('text:' + textCounter, content), {
            content: content,
        });
    }

    function fieldDisplayText(field) {
        if (isTextField(field)) {
            return field.content || field.label || @json(__('certificates.static_text'));
        }
        return sampleValues[field.key] || field.label || field.key;
    }

    function clearGuides() {
        guideLayer.innerHTML = '';
    }

    function showGuide(axis, position) {
        const line = document.createElement('div');
        line.className = 'cert-guide-line ' + (axis === 'x' ? 'vertical' : 'horizontal');
        if (axis === 'x') {
            line.style.left = position + 'px';
        } else {
            line.style.top = position + 'px';
        }
        guideLayer.appendChild(line);
    }

    function collectAnchors(excludeIndex) {
        const anchors = { x: [0, CANVAS_W / 2, CANVAS_W], y: [0, CANVAS_H / 2, CANVAS_H] };

        fields.forEach(function (f, i) {
            if (i === excludeIndex) return;
            anchors.x.push(f.x, f.x + f.width / 2, f.x + f.width);
            anchors.y.push(f.y, f.y + 16);
        });

        return anchors;
    }

    function snapValue(movingEdges, anchors) {
        let best = null;
        let bestDist = SNAP_THRESHOLD + 1;

        movingEdges.forEach(function (edge) {
            anchors.forEach(function (anchor) {
                const dist = Math.abs(edge.value - anchor);
                if (dist <= SNAP_THRESHOLD && dist < bestDist) {
                    bestDist = dist;
                    best = { snapped: anchor, delta: anchor - edge.value, guides: [anchor] };
                }
            });
        });

        return best;
    }

    function applyAlignment(field, index) {
        if (!alignmentToggle.checked) {
            clearGuides();
            return;
        }

        const anchors = collectAnchors(index);
        const xEdges = [
            { value: field.x, adjust: function (d) { field.x += d; } },
            { value: field.x + field.width / 2, adjust: function (d) { field.x += d; } },
            { value: field.x + field.width, adjust: function (d) { field.x += d; } },
        ];
        const yEdges = [
            { value: field.y, adjust: function (d) { field.y += d; } },
        ];

        clearGuides();
        const snapX = snapValue(xEdges.map(e => ({ value: e.value })), anchors.x);
        if (snapX) {
            field.x += snapX.delta;
            snapX.guides.forEach(function (g) { showGuide('x', g); });
        }
        const snapY = snapValue(yEdges.map(e => ({ value: e.value })), anchors.y);
        if (snapY) {
            field.y += snapY.delta;
            snapY.guides.forEach(function (g) { showGuide('y', g); });
        }
    }

    function renderCanvas() {
        canvas.querySelectorAll('.cert-canvas-field').forEach(el => el.remove());
        fields.forEach(function (field, index) {
            const el = document.createElement('div');
            el.className = 'cert-canvas-field' + (isTextField(field) ? ' is-text' : '');
            el.dataset.index = String(index);
            el.style.left = field.x + 'px';
            el.style.top = field.y + 'px';
            el.style.width = field.width + 'px';
            el.style.fontSize = field.font_size + 'px';
            el.style.fontWeight = field.font_weight;
            el.style.color = field.color;
            el.style.textAlign = field.text_align;
            el.textContent = fieldDisplayText(field);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-field';
            removeBtn.innerHTML = '&times;';
            removeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                fields.splice(index, 1);
                selectedEl = null;
                inspector.style.display = 'none';
                clearGuides();
                renderCanvas();
            });
            el.appendChild(removeBtn);
            el.addEventListener('mousedown', startDrag);
            el.addEventListener('click', function (e) {
                e.stopPropagation();
                selectField(el, index);
            });
            canvas.appendChild(el);
        });
    }

    function selectField(el, index) {
        canvas.querySelectorAll('.cert-canvas-field').forEach(n => n.classList.remove('selected'));
        el.classList.add('selected');
        selectedEl = { el: el, index: index };
        inspector.style.display = 'block';
        const field = fields[index];

        if (isTextField(field)) {
            inspectorContentWrap.style.display = 'block';
            document.getElementById('inspectorContent').value = field.content || '';
        } else {
            inspectorContentWrap.style.display = 'none';
        }

        document.getElementById('inspectorFontSize').value = field.font_size;
        document.getElementById('inspectorFontWeight').value = field.font_weight;
        document.getElementById('inspectorColor').value = field.color;
        document.getElementById('inspectorAlign').value = field.text_align;
        document.getElementById('inspectorWidth').value = field.width;
    }

    function syncInspectorToField() {
        if (!selectedEl) return;
        const field = fields[selectedEl.index];
        if (isTextField(field)) {
            field.content = document.getElementById('inspectorContent').value;
            field.label = field.content;
        }
        field.font_size = parseInt(document.getElementById('inspectorFontSize').value, 10) || 20;
        field.font_weight = document.getElementById('inspectorFontWeight').value;
        field.color = document.getElementById('inspectorColor').value;
        field.text_align = document.getElementById('inspectorAlign').value;
        field.width = parseInt(document.getElementById('inspectorWidth').value, 10) || 300;
        renderCanvas();
        selectField(canvas.querySelector('[data-index="' + selectedEl.index + '"]'), selectedEl.index);
    }

    function startDrag(e) {
        if (e.target.classList.contains('remove-field')) return;
        const el = e.currentTarget;
        const index = parseInt(el.dataset.index, 10);
        selectField(el, index);
        const rect = canvas.getBoundingClientRect();
        const field = fields[index];
        dragOffset.x = (e.clientX - rect.left) / scale - field.x;
        dragOffset.y = (e.clientY - rect.top) / scale - field.y;

        function onMove(ev) {
            field.x = Math.max(0, Math.min(CANVAS_W - 40, (ev.clientX - rect.left) / scale - dragOffset.x));
            field.y = Math.max(0, Math.min(CANVAS_H - 20, (ev.clientY - rect.top) / scale - dragOffset.y));
            applyAlignment(field, index);
            el.style.left = field.x + 'px';
            el.style.top = field.y + 'px';
        }
        function onUp() {
            clearGuides();
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
        }
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
    }

    document.getElementById('fieldPalette').addEventListener('click', function (e) {
        const chip = e.target.closest('.field-chip');
        if (!chip) return;
        fields.push(defaultField(chip.dataset.key, chip.dataset.label));
        renderCanvas();
    });

    document.getElementById('addTextFieldBtn').addEventListener('click', function () {
        const content = window.prompt(@json(__('certificates.add_text_prompt')), @json(__('certificates.default_title')));
        if (content === null) return;
        const trimmed = content.trim();
        if (!trimmed) return;
        fields.push(defaultTextField(trimmed));
        renderCanvas();
        selectField(canvas.querySelector('[data-index="' + (fields.length - 1) + '"]'), fields.length - 1);
    });

    ['inspectorFontSize', 'inspectorFontWeight', 'inspectorColor', 'inspectorAlign', 'inspectorWidth', 'inspectorContent'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', syncInspectorToField);
    });

    canvas.addEventListener('click', function () {
        clearGuides();
    });

    form.addEventListener('submit', function () {
        layoutInput.value = JSON.stringify(fields);
    });

    document.getElementById('saveAndPublishBtn').addEventListener('click', function () {
        document.getElementById('publishFlag').disabled = false;
        form.querySelector('input[name="is_published"][value="0"]').disabled = true;
        layoutInput.value = JSON.stringify(fields);
        form.requestSubmit();
    });

    document.getElementById('formSelect').addEventListener('change', function () {
        if (!confirm(@json(__('certificates.form_change_confirm')))) {
            this.value = '{{ $form->id }}';
            return;
        }
        const url = new URL(window.location.href);
        url.searchParams.set('form_id', this.value);
        window.location = url.toString();
    });

    renderCanvas();
});
</script>
@endpush
