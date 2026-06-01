@php
    $titleIndex = $titleIndex ?? '__TITLE_INDEX__';
    $selectedTitle = $selectedTitle ?? '';
    $grades = $grades ?? [];
@endphp
<div class="academic-title-block border rounded p-2 mb-2 bg-white" data-title-block>
    <div class="d-flex gap-2 align-items-start mb-2">
        <div class="flex-grow-1">
            <label class="form-label small mb-1">{{ __('forms.academic_title') }}</label>
            <select name="title_grades[{{ $titleIndex }}][academic_title]" class="form-select form-select-sm" required>
                <option value="">{{ __('forms.select_title') }}</option>
                @foreach($academicTitleOptions ?? [] as $opt)
                    <option value="{{ $opt }}" @selected($selectedTitle === $opt)>{{ $opt }}</option>
                @endforeach
                @if($selectedTitle && !collect($academicTitleOptions ?? [])->contains($selectedTitle))
                    <option value="{{ $selectedTitle }}" selected>{{ $selectedTitle }} {{ __('common.legacy') }}</option>
                @endif
            </select>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm mt-4 remove-title-block" title="{{ __('forms.remove_title') }}">
            <i class="bi bi-trash"></i>
        </button>
    </div>
    <label class="form-label small mb-1">{{ __('forms.grade_bands') }}</label>
    <div class="grade-rows mb-2" data-grade-container>
        @forelse($grades as $gi => $g)
            @include('forms.partials.title_grade_row', [
                'titleIndex' => $titleIndex,
                'gradeIndex' => $gi,
                'grade' => $g,
            ])
        @empty
        @endforelse
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm add-grade-row">
        <i class="bi bi-plus"></i> {{ __('forms.add_grade') }}
    </button>
</div>
