@php
    $user = auth()->user();
@endphp

<aside class="tqa-sidebar">
    <div class="brand">
        <i class="bi bi-mortarboard-fill"></i>
        <span>{{ config('app.name', 'TQA') }}</span>
    </div>

    <nav class="flex-grow-1 py-2">
        @role('Super Admin')
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        @endrole

        @can('committees.manage')
            <div class="nav-section">Evaluation</div>
            <a class="nav-link {{ request()->routeIs('committees.*') ? 'active' : '' }}" href="{{ route('committees.index') }}">
                <i class="bi bi-people"></i> Committees
            </a>
            <a class="nav-link {{ request()->routeIs('evaluations.*') ? 'active' : '' }}" href="{{ route('evaluations.index') }}">
                <i class="bi bi-clipboard-check"></i> Evaluations
            </a>
        @elsecan('evaluations.submit')
            <div class="nav-section">Evaluation</div>
            <a class="nav-link {{ request()->routeIs('evaluations.*') ? 'active' : '' }}" href="{{ route('evaluations.index') }}">
                <i class="bi bi-clipboard-check"></i> My Evaluations
            </a>
        @endcan

        @can('reports.view')
            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                <i class="bi bi-graph-up"></i> Reports
            </a>
        @endcan

        @canany(['colleges.manage','departments.manage','staff.manage','viewAny,App\Models\StaffMember','staff_options.manage','staff_status.manage'])
            <div class="nav-section">Organization</div>
            @can('colleges.manage')
                <a class="nav-link {{ request()->routeIs('colleges.*') ? 'active' : '' }}" href="{{ route('colleges.index') }}">
                    <i class="bi bi-building"></i> Colleges
                </a>
            @endcan
            @can('departments.manage')
                <a class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.index') }}">
                    <i class="bi bi-diagram-3"></i> Departments
                </a>
            @endcan
            @canany(['staff.manage', 'viewAny,App\Models\StaffMember'])
                <a class="nav-link {{ request()->routeIs('staff.*') ? 'active' : '' }}" href="{{ route('staff.index') }}">
                    <i class="bi bi-person-vcard"></i> Staff Members
                </a>
            @endcanany
            @canany(['staff_options.manage', 'staff_status.manage'])
                <a class="nav-link {{ request()->routeIs('staff-options.*', 'staff-statuses.*') ? 'active' : '' }}" href="{{ route('staff-options.index') }}">
                    <i class="bi bi-list-check"></i> Staff Field Options
                </a>
            @endcanany
            @can('org_roles.manage')
                <a class="nav-link {{ request()->routeIs('org-roles.*') ? 'active' : '' }}" href="{{ route('org-roles.index') }}">
                    <i class="bi bi-person-badge"></i> Organizational Roles
                </a>
            @endcan
        @endcanany

        @canany(['forms.manage','periods.manage'])
            <div class="nav-section">Evaluation Setup</div>
            @can('forms.manage')
                <a class="nav-link {{ request()->routeIs('forms.*') ? 'active' : '' }}" href="{{ route('forms.index') }}">
                    <i class="bi bi-ui-checks"></i> Forms
                </a>
            @endcan
            @can('periods.manage')
                <a class="nav-link {{ request()->routeIs('periods.*') ? 'active' : '' }}" href="{{ route('periods.index') }}">
                    <i class="bi bi-calendar-range"></i> Evaluation Periods
                </a>
            @endcan
        @endcanany

        @role('Super Admin')
            <a class="nav-link {{ request()->routeIs('super-admins.*') ? 'active' : '' }}" href="{{ route('super-admins.index') }}">
                <i class="bi bi-shield-lock"></i> Super Admins
            </a>
            <a class="nav-link {{ request()->routeIs('master-data.*') ? 'active' : '' }}" href="{{ route('master-data.index') }}">
                <i class="bi bi-database-gear"></i> Master Data CSV
            </a>
        @endrole

        @canany(['users.manage','coordinators.manage','activity_log.view'])
            <div class="nav-section">Administration</div>
            @can('coordinators.manage')
                <a class="nav-link {{ request()->routeIs('coordinators.*') ? 'active' : '' }}" href="{{ route('coordinators.index') }}">
                    <i class="bi bi-person-plus"></i> Quality Coordinators
                </a>
            @endcan
            @can('users.manage')
                <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-people-fill"></i> Users
                </a>
            @endcan
            @can('activity_log.view')
                <a class="nav-link {{ request()->routeIs('activity-log.*') ? 'active' : '' }}" href="{{ route('activity-log.index') }}">
                    <i class="bi bi-clock-history"></i> Activity Log
                </a>
            @endcan
        @endcanany
    </nav>

    <div class="nav-foot">
        Signed in as<br>
        <strong class="text-white">{{ $user?->name }}</strong><br>
        <span class="text-info-emphasis small">{{ $user?->email }}</span>
        @if($user && $user->roles->count())
            <div class="mt-2">
                @foreach($user->roles as $r)
                    <span class="badge bg-primary-subtle text-primary-emphasis">{{ $r->name }}</span>
                @endforeach
            </div>
        @endif
    </div>
</aside>
