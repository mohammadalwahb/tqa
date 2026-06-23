@extends('layouts.app')

@section('title', $template->exists ? __('certificates.edit_template') : __('certificates.design'))

@push('styles')
<style>
.cert-designer { display: grid; grid-template-columns: 260px 1fr; gap: 1rem; }
@media (max-width: 992px) { .cert-designer { grid-template-columns: 1fr; } }
.cert-palette .field-chip {
    cursor: grab;
    user-select: none;
    border: 1px dashed #cbd5e1;
    border-radius: .375rem;
    padding: .5rem .75rem;
    margin-bottom: .5rem;
    background: #fff;
    font-size: .875rem;
}
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
.cert-canvas-field {
    position: absolute;
    border: 1px dashed #2563eb;
    background: rgba(37, 99, 235, 0.08);
    cursor: move;
    padding: 2px 4px;
    overflow: hidden;
    line-height: 1.2;
}
.cert-canvas-field.selected { border-style: solid; background: rgba(37, 99, 235, 0.15); }
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
}
.cert-canvas-field.selected .remove-field { display: block; }
</style>
@endpush

@section('content')
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
                <div class="card-header"><strong>{{ __('certificates.available_fields') }}</strong></div>
                <div class="card-body" id="fieldPalette">
                    @foreach($fieldCatalog as $field)
                        <div class="field-chip" data-key="{{ $field['key'] }}" data-label="{{ $field['label'] }}">
                            <i class="bi bi-plus-circle text-primary"></i> {{ $field['label'] }}
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card table-card mt-3" id="fieldInspector" style="display:none;">
                <div class="card-header"><strong>{{ __('certificates.field_settings') }}</strong></div>
                <div class="card-body">
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
                    <div class="mb-2">
                        <label class="form-label small">{{ __('certificates.field_width') }}</label>
                        <input type="number" class="form-control form-control-sm" id="inspectorWidth" min="40" max="1000" value="300">
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong>{{ __('certificates.canvas_title') }}</strong>
                    <span class="text-muted small">A4 {{ __('certificates.landscape') }}</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save"></i> {{ __('common.save') }}</button>
                    <button type="button" class="btn btn-success btn-sm" id="saveAndPublishBtn"><i class="bi bi-check-circle"></i> {{ __('certificates.save_publish') }}</button>
                </div>
            </div>
            <div class="cert-canvas-wrap">
                <div class="cert-canvas" id="certCanvas"
                     style="width:{{ \App\Models\CertificateTemplate::CANVAS_WIDTH }}px;height:{{ \App\Models\CertificateTemplate::CANVAS_HEIGHT }}px;@if($template->backgroundUrl())background-image:url('{{ $template->backgroundUrl() }}');@endif">
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('certCanvas');
    const form = document.getElementById('certificateDesignerForm');
    const layoutInput = document.getElementById('layoutJson');
    const inspector = document.getElementById('fieldInspector');
    const scale = Math.min(1, (canvas.parentElement.clientWidth - 32) / {{ \App\Models\CertificateTemplate::CANVAS_WIDTH }});
    canvas.style.transform = 'scale(' + scale + ')';
    canvas.style.marginBottom = (({{ \App\Models\CertificateTemplate::CANVAS_HEIGHT }} * scale) - {{ \App\Models\CertificateTemplate::CANVAS_HEIGHT }}) + 'px';

    let fields = @json($placedFields);
    let selectedEl = null;
    let dragOffset = { x: 0, y: 0 };

    const sampleValues = {
        full_name_en: @json(__('certificates.sample_name')),
        college: @json(__('certificates.sample_college')),
        department: @json(__('certificates.sample_department')),
        academic_title: @json(__('certificates.sample_title')),
    };

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

    function renderCanvas() {
        canvas.querySelectorAll('.cert-canvas-field').forEach(el => el.remove());
        fields.forEach((field, index) => {
            const el = document.createElement('div');
            el.className = 'cert-canvas-field';
            el.dataset.index = String(index);
            el.style.left = field.x + 'px';
            el.style.top = field.y + 'px';
            el.style.width = field.width + 'px';
            el.style.fontSize = field.font_size + 'px';
            el.style.fontWeight = field.font_weight;
            el.style.color = field.color;
            el.style.textAlign = field.text_align;
            const sample = sampleValues[field.key] || field.label;
            el.textContent = sample;
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-field';
            removeBtn.innerHTML = '&times;';
            removeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                fields.splice(index, 1);
                selectedEl = null;
                inspector.style.display = 'none';
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
        selectedEl = { el, index };
        inspector.style.display = 'block';
        const field = fields[index];
        document.getElementById('inspectorFontSize').value = field.font_size;
        document.getElementById('inspectorFontWeight').value = field.font_weight;
        document.getElementById('inspectorColor').value = field.color;
        document.getElementById('inspectorAlign').value = field.text_align;
        document.getElementById('inspectorWidth').value = field.width;
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
            field.x = Math.max(0, Math.min({{ \App\Models\CertificateTemplate::CANVAS_WIDTH }} - 40, (ev.clientX - rect.left) / scale - dragOffset.x));
            field.y = Math.max(0, Math.min({{ \App\Models\CertificateTemplate::CANVAS_HEIGHT }} - 20, (ev.clientY - rect.top) / scale - dragOffset.y));
            el.style.left = field.x + 'px';
            el.style.top = field.y + 'px';
        }
        function onUp() {
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

    ['inspectorFontSize', 'inspectorFontWeight', 'inspectorColor', 'inspectorAlign', 'inspectorWidth'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', function () {
            if (!selectedEl) return;
            const field = fields[selectedEl.index];
            field.font_size = parseInt(document.getElementById('inspectorFontSize').value, 10) || 20;
            field.font_weight = document.getElementById('inspectorFontWeight').value;
            field.color = document.getElementById('inspectorColor').value;
            field.text_align = document.getElementById('inspectorAlign').value;
            field.width = parseInt(document.getElementById('inspectorWidth').value, 10) || 300;
            renderCanvas();
            selectField(canvas.querySelector('[data-index="' + selectedEl.index + '"]'), selectedEl.index);
        });
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
