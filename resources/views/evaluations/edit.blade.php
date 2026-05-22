@extends('layouts.app')

@section('title', 'Evaluate · ' . $evaluation->evaluatee->full_name_en)

@section('content')
@if(!empty($adminEdit) && $evaluation->isSubmitted())
    <div class="alert alert-warning">
        <i class="bi bi-shield-lock me-1"></i>
        <strong>Super Admin:</strong> You are editing a submitted evaluation. Changes are saved immediately and remain marked as submitted.
    </div>
@endif

<div class="card table-card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">{{ $evaluation->evaluatee->full_name_en }}</h5>
            <div class="text-muted small">
                {{ $evaluation->evaluatee->email }} · {{ $evaluation->evaluatee->department?->name_en }}
            </div>
            <div class="small mt-1">
                @if($evaluation->committee->type === 'local')
                    <span class="badge bg-primary">Local Committee</span>
                @else
                    <span class="badge bg-info">HD Committee</span>
                @endif
                · {{ $evaluation->committee->name ?? '#' . $evaluation->committee_id }}
            </div>
        </div>
        <a href="{{ route('evaluations.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
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
                <div class="card-header"><h6 class="mb-0">{{ $q->category?->name ?? 'General' }}</h6></div>
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
                @if($q->is_required)<span class="text-danger">*</span>@endif
                <span class="badge bg-light text-muted ms-1">{{ ucfirst($q->type) }}</span>
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
                    <small class="text-muted ms-2 align-self-center">(1 = Poor, 5 = Excellent)</small>
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
        <div class="alert alert-warning">No questions are visible to your role on this form.</div>
    @endif

    <div class="d-flex gap-2">
        <button class="btn btn-{{ !empty($adminEdit) && $evaluation->isSubmitted() ? 'primary' : 'outline-secondary' }}">
            <i class="bi bi-save"></i>
            {{ !empty($adminEdit) && $evaluation->isSubmitted() ? 'Save changes' : 'Save Draft' }}
        </button>
        @if(empty($adminEdit) || ! $evaluation->isSubmitted())
            <button type="submit" formaction="{{ route('evaluations.submit', $evaluation) }}" formmethod="POST"
                    class="btn btn-success" id="submitBtn">
                <i class="bi bi-check2-circle"></i> Submit Evaluation
            </button>
        @endif
        <a href="{{ route('evaluations.index') }}" class="btn btn-light">Cancel</a>
    </div>
</form>

@push('scripts')
@if(empty($adminEdit) || ! $evaluation->isSubmitted())
<script>
    document.getElementById('submitBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        Swal.fire({
            title: 'Submit this evaluation?',
            text: 'After submission, you will not be able to edit your answers.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            confirmButtonText: 'Yes, submit',
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
