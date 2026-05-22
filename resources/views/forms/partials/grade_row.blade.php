@php
    $idx = $index;
    $label = $grade->label ?? '';
    $min = $grade->min_value ?? '';
    $max = $grade->max_value ?? '';
@endphp
<div class="grade-row row g-1 align-items-center mb-1">
    <div class="col-3">
        <input type="text" name="grades[{{ $idx }}][label]" data-grade-label
               class="form-control form-control-sm" placeholder="A+" value="{{ $label }}">
    </div>
    <div class="col-3">
        <input type="number" step="0.01" name="grades[{{ $idx }}][min_value]" data-grade-min
               class="form-control form-control-sm" placeholder="Min" value="{{ $min }}">
    </div>
    <div class="col-3">
        <input type="number" step="0.01" name="grades[{{ $idx }}][max_value]" data-grade-max
               class="form-control form-control-sm" placeholder="Max (empty = +)" value="{{ $max }}">
    </div>
    <div class="col-3">
        <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-grade-row" title="Remove">
            <i class="bi bi-dash"></i>
        </button>
    </div>
</div>
