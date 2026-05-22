@extends('layouts.app')

@section('title', 'Committees')

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">Committees</h5>
        <a href="{{ route('committees.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Create Committee</a>
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr><th>Type</th><th>Name</th><th>College / Department</th><th>Period</th><th class="text-end">Members</th><th class="text-end">Evaluations</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            @forelse($committees as $c)
                <tr>
                    <td>
                        @if($c->type === 'local')
                            <span class="badge bg-primary">Local</span>
                        @else
                            <span class="badge bg-info">HD</span>
                        @endif
                    </td>
                    <td><strong><a href="{{ route('committees.show', $c) }}">{{ $c->name ?? '#' . $c->id }}</a></strong></td>
                    <td>
                        <small class="text-muted">{{ $c->college?->name_en }}</small><br>
                        {{ $c->department?->name_en }}
                    </td>
                    <td><small>{{ $c->period?->name }}</small></td>
                    <td class="text-end">{{ $c->members->count() }}</td>
                    <td class="text-end">{{ $c->evaluations_count }}</td>
                    <td class="text-end">
                        <a href="{{ route('committees.show', $c) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-eye"></i></a>
                        <form action="{{ route('committees.destroy', $c) }}" method="POST" class="d-inline" data-confirm="Delete this committee and all its evaluations?">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">No committees yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
