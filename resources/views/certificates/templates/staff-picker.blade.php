@extends('layouts.app')

@section('title', __('certificates.view_staff'))

@section('content')
<div class="card table-card">
    <div class="card-header">
        <h5 class="mb-0">{{ __('certificates.view_staff') }} — {{ $template->period->name }}</h5>
        <a href="{{ route('certificate-templates.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> {{ __('common.back') }}</a>
    </div>
    <div class="card-body">
        <table class="table align-middle datatable">
            <thead class="table-light">
                <tr>
                    <th>{{ __('staff.full_name_en') }}</th>
                    <th>{{ __('common.department') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($staffRows as $row)
                @php $staff = $row['staff']; @endphp
                <tr>
                    <td>{{ $staff->full_name_en }}</td>
                    <td>{{ $staff->department?->name_en }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('certificate-templates.preview', [$template, $staff]) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="bi bi-image"></i> {{ __('certificates.view') }}
                        </a>
                        <a href="{{ route('certificate-templates.export.pdf', [$template, $staff]) }}" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-file-earmark-pdf"></i> PDF
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
