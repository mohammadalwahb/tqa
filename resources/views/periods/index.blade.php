@extends('layouts.app')

@section('title', 'Evaluation Periods')

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">Evaluation Periods</h5>
        <a href="{{ route('periods.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New Period</a>
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr><th>Name</th><th>Academic Year</th><th>Start</th><th>End</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            @foreach($periods as $p)
                <tr>
                    <td><strong>{{ $p->name }}</strong></td>
                    <td>{{ $p->academic_year ?? '—' }}</td>
                    <td>{{ $p->start_date->toDateString() }}</td>
                    <td>{{ $p->end_date->toDateString() }}</td>
                    <td>
                        @if($p->isOpen())
                            <span class="badge bg-success">Open</span>
                        @elseif($p->is_active)
                            <span class="badge bg-warning text-dark">Scheduled</span>
                        @else
                            <span class="badge bg-secondary">Closed</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('periods.edit', $p) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('periods.destroy', $p) }}" method="POST" class="d-inline" data-confirm="Delete period?">
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
