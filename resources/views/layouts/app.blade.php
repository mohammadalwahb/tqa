@php
    use App\Support\LocaleHelper;
    $appLocale = LocaleHelper::current();
    $appDirection = LocaleHelper::direction();
@endphp
<!DOCTYPE html>
<html lang="{{ $appLocale }}" dir="{{ $appDirection }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('nav.dashboard')) · {{ config('app.name', 'TQA') }}</title>

    @if($appDirection === 'rtl')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    @else
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    @endif
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.0/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.min.css">

    <style>
        :root {
            --tqa-primary: #1d4ed8;
            --tqa-primary-dark: #1e3a8a;
            --tqa-sidebar-bg: #0f172a;
            --tqa-sidebar-color: #e2e8f0;
            --tqa-sidebar-hover: #1e293b;
            --tqa-sidebar-active: #2563eb;
        }

        body {
            background: #f1f5f9;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .tqa-wrapper { display: flex; min-height: 100vh; }

        .tqa-sidebar {
            width: 260px;
            background: var(--tqa-sidebar-bg);
            color: var(--tqa-sidebar-color);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        .tqa-sidebar .brand {
            padding: 1.25rem 1rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,.06);
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .tqa-sidebar .brand i { color: #38bdf8; }
        .tqa-sidebar .nav-section {
            padding: .75rem 1rem .25rem;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
        }
        .tqa-sidebar a.nav-link {
            color: var(--tqa-sidebar-color);
            padding: .55rem 1rem;
            display: flex;
            align-items: center;
            gap: .65rem;
            border-left: 3px solid transparent;
            font-size: .92rem;
        }
        .tqa-sidebar a.nav-link i { width: 18px; text-align: center; }
        .tqa-sidebar a.nav-link:hover {
            background: var(--tqa-sidebar-hover);
            color: #fff;
        }
        .tqa-sidebar a.nav-link.active {
            background: var(--tqa-sidebar-hover);
            color: #fff;
            border-left-color: var(--tqa-sidebar-active);
        }
        .tqa-sidebar .nav-foot {
            margin-top: auto;
            padding: 1rem;
            font-size: .8rem;
            color: #94a3b8;
            border-top: 1px solid rgba(255,255,255,.05);
        }

        .tqa-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .tqa-topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .tqa-topbar .page-title { font-weight: 600; font-size: 1.05rem; margin: 0; }
        .tqa-content { padding: 1.5rem; flex: 1; }

        .stat-card { border: 0; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
        .stat-card .stat-value { font-size: 1.75rem; font-weight: 700; }
        .stat-card .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; color: #fff;
        }

        .table-card { background: #fff; border-radius: .5rem; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
        .table-card .card-header {
            background: transparent;
            border-bottom: 1px solid #e2e8f0;
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 1.25rem;
        }

        .sortable-handle { cursor: grab; color: #94a3b8; }
        .sortable-handle:active { cursor: grabbing; }

        @media (max-width: 992px) {
            .tqa-sidebar { position: fixed; left: -260px; transition: left .2s; z-index: 1050; }
            .tqa-sidebar.show { left: 0; }
        }

        [dir="rtl"] .tqa-sidebar a.nav-link {
            border-left: none;
            border-right: 3px solid transparent;
        }
        [dir="rtl"] .tqa-sidebar a.nav-link.active {
            border-right-color: var(--tqa-sidebar-active);
        }
        @media (max-width: 992px) {
            [dir="rtl"] .tqa-sidebar { left: auto; right: -260px; }
            [dir="rtl"] .tqa-sidebar.show { right: 0; left: auto; }
        }
        [dir="rtl"] body { font-family: 'Segoe UI', 'Noto Sans Arabic', 'Tahoma', system-ui, sans-serif; }

        .export-scroll-panel {
            max-height: min(60vh, 28rem);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
    </style>

    @stack('styles')
</head>
<body>
<div class="tqa-wrapper">
    @auth
        @include('partials.sidebar')
    @endauth

    <div class="tqa-main">
        @auth
            @include('partials.topbar')
        @endauth

        <main class="tqa-content">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-octagon me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="bi bi-exclamation-triangle me-1"></i> {{ __('common.whoops') }}</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                document.querySelector('.tqa-sidebar')?.classList.toggle('show');
            });
        }

        $('table.datatable').each(function () {
            if ($.fn.dataTable.isDataTable(this)) return;

            // Drop placeholder "no data" rows that use colspan — they would
            // confuse DataTables column auto-detection (causes tn/4 warning).
            $(this).find('tbody tr').filter(function () {
                return $(this).find('> td[colspan]').length > 0;
            }).remove();

            $(this).DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                order: [],
                dom: '<"d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"<"d-flex flex-wrap align-items-center gap-2"lB>f>rt<"d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2"ip>',
                buttons: $(this).data('buttons') === false ? [] : ['copy', 'csv', 'excel', 'print'],
                language: {
                    emptyTable: @json(__('common.no_records')),
                    zeroRecords: @json(__('common.no_matching')),
                    lengthMenu: @json(__('common.show_rows')),
                },
            });
        });

        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const message = form.dataset.confirm || @json(__('common.are_you_sure'));
                Swal.fire({
                    title: @json(__('common.are_you_sure')),
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: @json(__('common.yes_proceed')),
                }).then(function (result) {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    });
</script>

@stack('scripts')
</body>
</html>
