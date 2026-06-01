@extends('layouts.app')

@section('title', __('evaluations.evaluate_title', ['name' => \App\Support\LocaleHelper::staffDisplayName($evaluation->evaluatee)]))

@section('content')
@if(!empty($adminEdit) && $evaluation->isSubmitted())
    <div class="alert alert-warning">
        <i class="bi bi-shield-lock me-1"></i>
        {{ __('evaluations.super_admin_edit_notice') }}
    </div>
@endif

<div class="card table-card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">{{ \App\Support\LocaleHelper::staffDisplayName($evaluation->evaluatee) }}</h5>
            <div class="text-muted small">
                {{ $evaluation->evaluatee->email }} · {{ \App\Support\LocaleHelper::departmentDisplayName($evaluation->evaluatee->department) }}
            </div>
            <div class="small mt-1">
                <span class="badge bg-{{ $evaluation->committee->type === 'local' ? 'primary' : 'info' }}">
                    {{ $evaluation->committee->type === 'local' ? __('committees.tab_local') : __('committees.tab_hd') }}
                </span>
                · {{ $evaluation->committee->name ?? '#' . $evaluation->committee_id }}
            </div>
        </div>
        <a href="{{ route('evaluations.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> {{ __('common.back') }}</a>
    </div>
</div>

<form method="POST" action="{{ route('evaluations.update', $evaluation) }}" id="evalForm">
    @csrf @method('PUT')

    @php $currentCategoryId = '__none__'; @endphp
    @foreach($visibleQuestions as $q)
        @php $qCatKey = $q->evaluation_category_id ?? '__null__'; @endphp
        @if($qCatKey !== $currentCategoryId)
            @if($currentCategoryId !== '__none__') </div></div> @endif
            @php $currentCategoryId = $qCatKey; @endphp
            <div class="card table-card mb-3">
                <div class="card-header"><h6 class="mb-0">{{ $q->category?->name ?? __('evaluations.general') }}</h6></div>
                <div class="card-body">
        @endif

        @php
            $answer = $answersByQuestion->get($q->id);
            $rating = old("answers.$q->id.rating", $answer?->rating_value);
            $text   = old("answers.$q->id.text",   $answer?->text_value);
            $number = old("answers.$q->id.number", $answer?->number_value);
        @endphp

        <div class="mb-4 pb-3 border-bottom">
            <label class="form-label">
                <strong>{{ $loop->iteration }}. {{ $q->text }}</strong>
                @if($q->is_required)<span class="text-danger">{{ __('common.required_mark') }}</span>@endif
                <span class="badge bg-light text-muted ms-1">{{ \App\Support\LocaleHelper::enum('question_type', $q->type) }}</span>
            </label>
            @if($q->help_text)
                <div class="form-text mb-2">{{ $q->help_text }}</div>
            @endif

            @if($q->type === 'rating')
                <div class="d-flex gap-2 flex-wrap">
                    @for($i = config('tqa.rating.min', 1); $i <= config('tqa.rating.max', 5); $i++)
                        <input type="radio" class="btn-check" name="answers[{{ $q->id }}][rating]"
                               id="r{{ $q->id }}_{{ $i }}" value="{{ $i }}" autocomplete="off"
                               @checked((int) $rating === $i)>
                        <label class="btn btn-outline-primary" for="r{{ $q->id }}_{{ $i }}">{{ $i }}</label>
                    @endfor
                    <small class="text-muted ms-2 align-self-center">{{ __('evaluations.rating_scale') }}</small>
                </div>

            @elseif($q->type === 'text')
                <textarea name="answers[{{ $q->id }}][text]" rows="3"
                          class="form-control">{{ $text }}</textarea>

            @elseif($q->type === 'number')
                <input type="number" step="0.01" name="answers[{{ $q->id }}][number]"
                       value="{{ $number }}" class="form-control" style="max-width:240px;">
            @endif
        </div>
    @endforeach
    @if($visibleQuestions->isNotEmpty())
        </div></div>
    @else
        <div class="alert alert-warning">{{ __('evaluations.no_visible_questions') }}</div>
    @endif

    <div class="d-flex gap-2">
        <button class="btn btn-{{ !empty($adminEdit) && $evaluation->isSubmitted() ? 'primary' : 'outline-secondary' }}">
            <i class="bi bi-save"></i>
            {{ !empty($adminEdit) && $evaluation->isSubmitted() ? __('evaluations.save_changes') : __('evaluations.save_draft') }}
        </button>
        @if(empty($adminEdit) || ! $evaluation->isSubmitted())
            <button type="submit" formaction="{{ route('evaluations.submit', $evaluation) }}" formmethod="POST"
                    class="btn btn-success" id="submitBtn">
                <i class="bi bi-check2-circle"></i> {{ __('evaluations.submit') }}
            </button>
        @endif
        <a href="{{ route('evaluations.index') }}" class="btn btn-light">{{ __('common.cancel') }}</a>
    </div>
</form>

@push('scripts')
@if(empty($adminEdit) || ! $evaluation->isSubmitted())
<script>
    document.getElementById('submitBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        Swal.fire({
            title: @json(__('evaluations.submit_confirm')),
            text: @json(__('evaluations.submit_confirm_body')),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            confirmButtonText: @json(__('common.yes_submit')),
        }).then((r) => {
            if (!r.isConfirmed) return;
            const form = document.getElementById('evalForm');
            form.action = "{{ route('evaluations.submit', $evaluation) }}";
            const methodInput = form.querySelector('input[name=_method]');
            if (methodInput) methodInput.remove();
            form.submit();
        });
    });
</script>
@endif
@endpush
@endsection
