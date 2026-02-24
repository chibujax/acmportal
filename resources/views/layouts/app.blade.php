<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ACM Portal') – Abia Community Manchester</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('logo.jpg') }}">

    <!-- Bootstrap 5 CDN (no Node/build needed) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --acm-green:  #1a6b3c;
            --acm-gold:   #c8a84b;
            --acm-dark:   #0f3d22;
            --sidebar-w:  260px;
        }
        body { background: #f5f7fa; font-family: 'Segoe UI', system-ui, sans-serif; }

        /* Sidebar */
        #sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--acm-dark);
            position: fixed;
            top: 0; left: 0;
            transition: transform .3s;
            z-index: 1000;
        }
        #sidebar .brand {
            padding: 1.25rem 1.5rem;
            background: var(--acm-green);
            color: #fff;
            font-weight: 700;
            font-size: 1.05rem;
            border-bottom: 2px solid var(--acm-gold);
        }
        #sidebar .brand small { color: var(--acm-gold); font-size: .75rem; }
        #sidebar .nav-link {
            color: rgba(255,255,255,.75);
            padding: .6rem 1.5rem;
            border-radius: 0;
            transition: all .2s;
        }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.12);
        }
        #sidebar .nav-section {
            padding: .5rem 1.5rem .25rem;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255,255,255,.35);
            margin-top: .5rem;
        }

        /* Main content */
        #main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            transition: margin .3s;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: .75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 900;
        }
        .page-content { padding: 1.5rem; }

        /* Stat card icon */
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        /* Member status badges */
        .badge-active    { background: #d1fae5; color: #065f46; }
        .badge-inactive  { background: #f3f4f6; color: #374151; }
        .badge-suspended { background: #fee2e2; color: #991b1b; }

        /* Mobile sidebar */
        @media (max-width: 991.98px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main-content { margin-left: 0; }
        }

        /* Overlay for mobile */
        #sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 999;
        }
        #sidebar-overlay.show { display: block; }
    </style>

    @stack('styles')
</head>
<body>

@auth
<!-- Sidebar Overlay (mobile) -->
<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<nav id="sidebar">
    <div class="brand">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('logo.jpg') }}" alt="ACM" style="height:36px; object-fit:contain; border-radius:4px">
            ACM Portal
        </div>
        <small>Abia Community Manchester</small>
    </div>

    @php $user = auth()->user(); @endphp

    <div class="py-2">

        @if($user->isAdmin() || $user->isFinancialSecretary())

            {{-- ADMIN / FINANCIAL SECRETARY NAV --}}
            <div class="nav-section">Overview</div>
            <a href="{{ route('admin.dashboard') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <div class="nav-section">Members</div>
            <a href="{{ route('admin.members.index') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.members.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> All Members
            </a>
            <a href="{{ route('admin.members.import') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.members.import*') ? 'active' : '' }}">
                <i class="bi bi-upload"></i> Import (CSV)
            </a>
            <a href="{{ route('admin.members.pending') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.members.pending') ? 'active' : '' }}">
                <i class="bi bi-person-plus"></i> Pending Invites
            </a>

            <div class="nav-section">Attendance</div>
            <a href="{{ route('admin.meetings.index') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.meetings.index') || request()->routeIs('admin.meetings.create') || request()->routeIs('admin.meetings.show') || request()->routeIs('admin.meetings.edit') ? 'active' : '' }}">
                <i class="bi bi-calendar-event"></i> Meetings
            </a>
            <a href="{{ route('admin.meetings.report') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.meetings.report') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i> Attendance Report
            </a>

            <div class="nav-section">Finance</div>
            <a href="{{ route('admin.payments.index') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i> Payments
            </a>
            <a href="{{ route('admin.reports.financial') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.reports.financial') ? 'active' : '' }}">
                <i class="bi bi-graph-up"></i> Financial Report
            </a>
            <a href="{{ route('admin.reports.arrears') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('admin.reports.arrears') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle"></i> Arrears
            </a>

        @else

            {{-- MEMBER NAV --}}
            <div class="nav-section">My Portal</div>
            <a href="{{ route('member.dashboard') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('member.dashboard') ? 'active' : '' }}">
                <i class="bi bi-house"></i> Dashboard
            </a>
            <a href="{{ route('member.attendance') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('member.attendance') ? 'active' : '' }}">
                <i class="bi bi-calendar-check"></i> My Attendance
            </a>
            <a href="{{ route('member.payments') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('member.payments') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Payment History
            </a>
            <a href="{{ route('member.profile') }}"
               class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('member.profile') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i> My Profile
            </a>

        @endif

    </div>

    {{-- Logout (inside scroll flow, visible in sidebar) --}}
    <div class="p-3 mt-2 border-top border-secondary">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-sm w-100 text-white border-0"
                    style="background:rgba(255,255,255,.1)">
                <i class="bi bi-box-arrow-left me-2"></i>Sign Out
            </button>
        </form>
    </div>
</nav>

<!-- Main content -->
<div id="main-content">
    <!-- Topbar -->
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm d-lg-none" onclick="toggleSidebar()">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h6 class="mb-0 fw-semibold text-dark">@yield('page-title', 'ACM Portal')</h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="small text-muted d-none d-sm-inline">{{ auth()->user()->name }}</span>
            <span class="badge" style="background:{{ auth()->user()->isAdmin() ? '#1a6b3c' : (auth()->user()->isFinancialSecretary() ? '#1d4ed8' : '#6b7280') }}">
                {{ ucfirst(str_replace('_',' ', auth()->user()->role)) }}
            </span>
            <form method="POST" action="{{ route('logout') }}" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Sign Out">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-md-inline ms-1">Sign Out</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Page content -->
    <div class="page-content">

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if($errors->any() && !$errors->has('login') && !$errors->has('password'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            @foreach($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @yield('content')
    </div>
</div>

@else
    {{-- Guest pages (login, register) --}}
    @yield('content')
@endauth

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebar-overlay').classList.toggle('show');
}
</script>

@stack('scripts')
</body>
</html>
