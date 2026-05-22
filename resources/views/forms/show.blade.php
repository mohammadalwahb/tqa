@extends('layouts.app')

@section('title', $form->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">{{ $form->name }}</h4>
        <div class="text-muted small">{{ $form->description }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('forms.edit', $form) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Edit Form</a>
        <a href="{{ route('forms.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card table-card mb-3">
            <div class="card-header"><h6 class="mb-0">Categories</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('forms.categories.store', $form) }}" class="mb-3">
                    @csrf
                    <div class="d-flex gap-1 mb-2">
                        <input type="text" name="name" required placeholder="New category" class="form-control form-control-sm">
                        <button class="btn btn-primary btn-sm"><i class="bi bi-plus"></i></button>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="include_in_final_score" value="1"
                               id="newCatIncludeScore" checked>
                        <label class="form-check-label small" for="newCatIncludeScore">Include in final score</label>
                    </div>
                </form>

                <ul class="list-group list-group-flush">
                    @forelse($form->categories as $cat)
                        <li class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong>{{ $cat->name }}</strong>
                                    <span class="badge bg-light text-muted">{{ $cat->questions->count() }}</span>
                                    @unless($cat->include_in_final_score)
                                        <span class="badge bg-warning-subtle text-warning-emphasis">Excluded from final</span>
                                    @endunless
                                </div>
                                <form action="{{ route('forms.categories.destroy', [$form, $cat]) }}" method="POST"
                                      data-confirm="Delete category? Questions will remain.">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-x"></i></button>
                                </form>
                            </div>
                            <form method="POST" action="{{ route('forms.categories.update', [$form, $cat]) }}" class="mt-2">
                                @csrf @method('PUT')
                                <input type="hidden" name="include_in_final_score" value="0">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="include_in_final_score" value="1"
                                           id="catScore{{ $cat->id }}" @checked($cat->include_in_final_score)
                                           onchange="this.form.submit()">
                                    <label class="form-check-label small" for="catScore{{ $cat->id }}">
                                        Include in final score
                                    </label>
                                </div>
                            </form>
                        </li>
                    @empty
                        <li class="list-group-item text-muted small px-0">No categories yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        @include('forms.partials.score_metrics', ['form' => $form, 'academicTitleOptions' => $academicTitleOptions ?? []])

        <div class="card table-card">
            <div class="card-header"><h6 class="mb-0">Add Question</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('forms.questions.store', $form) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small">Text</label>
                        <textarea name="text" rows="2" class="form-control form-control-sm" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Help text</label>
                        <input type="text" name="help_text" class="form-control form-control-sm">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col">
                            <label class="form-label small">Type</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="rating">Rating (1-5)</option>
                                <option value="text">Text Note</option>
                                <option value="number">Number</option>
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label small">Category</label>
                            <select name="evaluation_category_id" class="form-select form-select-sm">
                                <option value="">— None —</option>
                                @foreach($form->categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Visible to roles</label>
                        <div>
                            @foreach($roles as $r)
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" name="roles[]" value="{{ $r->id }}"
                                           id="addrole_{{ $r->id }}" class="form-check-input" checked>
                                    <label for="addrole_{{ $r->id }}" class="form-check-label small">{{ $r->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_required" value="1" id="isReq" checked>
                        <label class="form-check-label small" for="isReq">Required</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="show_in_reports" value="0">
                        <input class="form-check-input" type="checkbox" name="show_in_reports" value="1" id="isShowReports" checked>
                        <label class="form-check-label small" for="isShowReports">Show in reports</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_enabled" value="1" id="isEna" checked>
                        <label class="form-check-label small" for="isEna">Enabled</label>
                    </div>
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-circle"></i> Add Question</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header"><h6 class="mb-0">Questions <small class="text-muted">(drag to reorder)</small></h6></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" id="questionList" data-form-id="{{ $form->id }}">
                    @forelse($form->questions->sortBy('sort_order') as $q)
                        <li class="list-group-item" data-id="{{ $q->id }}">
                            <div class="d-flex gap-2 align-items-start">
                                <i class="bi bi-grip-vertical sortable-handle pt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="d-flex gap-2 align-items-center mb-1 flex-wrap">
                                        <span class="badge bg-{{ $q->type === 'rating' ? 'primary' : ($q->type === 'text' ? 'secondary' : 'info') }}">
                                            {{ ucfirst($q->type) }}
                                        </span>
                                        @if($q->category)
                                            <span class="badge bg-light text-muted">{{ $q->category->name }}</span>
                                        @endif
                                        @if(!$q->is_enabled)
                                            <span class="badge bg-warning text-dark">Disabled</span>
                                        @endif
                                        @if($q->is_required)
                                            <span class="badge bg-danger-subtle text-danger-emphasis">Required</span>
                                        @endif
                                        @unless($q->show_in_reports)
                                            <span class="badge bg-warning-subtle text-warning-emphasis">Hidden from reports</span>
                                        @endunless
                                    </div>
                                    <div>{{ $q->text }}</div>
                                    @if($q->help_text)
                                        <small class="text-muted">{{ $q->help_text }}</small>
                                    @endif
                                    <div class="mt-1">
                                        @foreach($q->visibleToRoles as $role)
                                            <span class="badge bg-info-subtle text-info-emphasis">{{ $role->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#editQ{{ $q->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('forms.questions.destroy', [$form, $q]) }}" method="POST" data-confirm="Delete question?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </li>

                        <div class="modal fade" id="editQ{{ $q->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST" action="{{ route('forms.questions.update', [$form, $q]) }}" class="modal-content">
                                    @csrf @method('PUT')
                                    <div class="modal-header"><h5 class="modal-title">Edit question</h5>
                                        <button class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <label class="form-label small">Text</label>
                                            <textarea name="text" rows="2" class="form-control" required>{{ $q->text }}</textarea>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Help text</label>
                                            <input type="text" name="help_text" class="form-control" value="{{ $q->help_text }}">
                                        </div>
                                        <div class="row g-2 mb-2">
                                            <div class="col">
                                                <label class="form-label small">Type</label>
                                                <select name="type" class="form-select">
                                                    @foreach(['rating','text','number'] as $t)
                                                        <option value="{{ $t }}" @selected($q->type === $t)>{{ ucfirst($t) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col">
                                                <label class="form-label small">Category</label>
                                                <select name="evaluation_category_id" class="form-select">
                                                    <option value="">— None —</option>
                                                    @foreach($form->categories as $cat)
                                                        <option value="{{ $cat->id }}" @selected($q->evaluation_category_id == $cat->id)>{{ $cat->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Visible to roles</label>
                                            <div>
                                                @foreach($roles as $r)
                                                    <div class="form-check form-check-inline">
                                                        <input type="checkbox" name="roles[]" value="{{ $r->id }}"
                                                               id="er{{ $q->id }}_{{ $r->id }}" class="form-check-input"
                                                               @checked($q->visibleToRoles->contains($r->id))>
                                                        <label for="er{{ $q->id }}_{{ $r->id }}" class="form-check-label small">{{ $r->name }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_required" value="1" id="req{{ $q->id }}" @checked($q->is_required)>
                                            <label class="form-check-label small" for="req{{ $q->id }}">Required</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="show_in_reports" value="0">
                                            <input class="form-check-input" type="checkbox" name="show_in_reports" value="1"
                                                   id="showReports{{ $q->id }}" @checked($q->show_in_reports)>
                                            <label class="form-check-label small" for="showReports{{ $q->id }}">Show in reports</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_enabled" value="1" id="ena{{ $q->id }}" @checked($q->is_enabled)>
                                            <label class="form-check-label small" for="ena{{ $q->id }}">Enabled</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                        <button class="btn btn-primary">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <li class="list-group-item text-muted text-center py-4">No questions yet. Use the panel on the left to add one.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const list = document.getElementById('questionList');
        if (!list || !window.Sortable) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        Sortable.create(list, {
            handle: '.sortable-handle',
            animation: 150,
            onEnd: function () {
                const order = Array.from(list.querySelectorAll('li[data-id]')).map(li => Number(li.dataset.id));
                fetch("{{ route('forms.questions.reorder', $form) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ order })
                });
            }
        });
    });
</script>
@endpush
@endsection
