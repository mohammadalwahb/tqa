@extends('layouts.app')

@section('title', 'Evaluation Forms')

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">Evaluation Forms</h5>
        <a href="{{ route('forms.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New Form</a>
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr><th>Name</th><th>Target</th><th class="text-end">Categories</th><th class="text-end">Questions</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            @foreach($forms as $f)
                <tr>
                    <td><strong><a href="{{ route('forms.show', $f) }}">{{ $f->name }}</a></strong>
                        @if($f->description)<br><small class="text-muted">{{ $f->description }}</small>@endif
                    </td>
                    <td>
                        @if($f->target_type === 'head_of_department')
                            <span class="badge bg-info-subtle text-info-emphasis">Head of Department</span>
                        @else
                            <span class="badge bg-primary-subtle text-primary-emphasis">Teaching Staff</span>
                        @endif
                    </td>
                    <td class="text-end">{{ $f->categories_count }}</td>
                    <td class="text-end">{{ $f->questions_count }}</td>
                    <td>
                        <span class="badge bg-{{ $f->is_active ? 'success-subtle text-success-emphasis' : 'secondary' }}">
                            {{ $f->is_active ? 'Active' : 'Disabled' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('forms.show', $f) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list-check"></i></a>
                        <a href="{{ route('forms.edit', $f) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('forms.destroy', $f) }}" method="POST" class="d-inline" data-confirm="Delete form?">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
