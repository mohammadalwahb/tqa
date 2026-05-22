@php $user = auth()->user(); @endphp
<header class="tqa-topbar">
    <button id="sidebarToggle" class="btn btn-outline-secondary btn-sm d-lg-none">
        <i class="bi bi-list"></i>
    </button>

    <h1 class="page-title">@yield('title', 'Dashboard')</h1>

    <div class="ms-auto d-flex align-items-center gap-3">
        @php
            $period = \App\Models\EvaluationPeriod::currentlyOpen();
        @endphp
        @if($period)
            <span class="badge bg-success-subtle text-success-emphasis">
                <i class="bi bi-circle-fill text-success me-1" style="font-size:.6rem;"></i>
                {{ $period->name }} (open until {{ $period->end_date->format('Y-m-d') }})
            </span>
        @else
            <span class="badge bg-warning-subtle text-warning-emphasis">
                <i class="bi bi-clock me-1"></i> No open evaluation period
            </span>
        @endif

        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center gap-2"
                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                @if($user?->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="avatar" width="24" height="24" class="rounded-circle">
                @else
                    <i class="bi bi-person-circle"></i>
                @endif
                <span class="d-none d-md-inline">{{ $user?->name }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text small text-muted">{{ $user?->email }}</span></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-1"></i> Sign out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
