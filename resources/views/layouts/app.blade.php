<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ACM Portal') – Abia Community Manchester</title>

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
            border-left: 3px solid var(--acm-gold);
        }
        #sidebar .nav-section {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: rgba(255,255,255,.35);
            padding: 1rem 1.5rem .25rem;
        }
        #sidebar .nav-link .bi { width: 20px; margin-right: .5rem; }

        /* Main content */
        #main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            transition: margin .3s;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: .75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .page-content { padding: 1.75rem 1.5rem; }

        /* Cards */
        .stat-card {
            border: none;
            border-radius: 12px;
            transition: transform .15s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }

        /* Badge colours */
        .badge-pending   { background: #fef3c7; color: #92400e; }
        .badge-active    { background: #d1fae5; color: #065f46; }
        .badge-suspended { background: #fee2e2; color: #991b1b; }

        /* Responsive sidebar */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main { margin-left: 0; }
        }
    </style>

    @stack('styles')
</head>
<body>

@auth
<!-- Sidebar -->
<nav id="sidebar">
    <div class="brand">
        <div>🦅 ACM Portal</div>
        <small>Abia Community Manchester</small>
    </div>

    <ul class="nav flex-column mt-2">
        @if(auth()->user()->isAdmin() || auth()->user()->isFinancialSecretary())
            <li class="nav-section">Administration</li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                   href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}"
                   href="{{ route('admin.members.index') }}">
                    <i class="bi bi-people"></i> Members
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.members.import') ? 'active' : '' }}"
                   href="{{ route('admin.members.import') }}">
                    <i class="bi bi-upload"></i> CSV Import
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.members.pending') ? 'active' : '' }}"
                   href="{{ route('admin.members.pending') }}">
                    <i class="bi bi-person-plus"></i> Pending Members
                </a>
            </li>
            <li class="nav-section">Finance</li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}"
                   href="{{ route('admin.payments.index') }}">
                    <i class="bi bi-cash-stack"></i> Manual Payments
                </a>
            </li>
            <li class="nav-section">Reports</li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.reports.financial') ? 'active' : '' }}"
                   href="{{ route('admin.reports.financial') }}">
                    <i class="bi bi-bar-chart"></i> Financial Report
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.reports.arrears') ? 'active' : '' }}"
                   href="{{ route('admin.reports.arrears') }}">
                    <i class="bi bi-exclamation-triangle"></i> Arrears Report
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.reports.members') ? 'active' : '' }}"
                   href="{{ route('admin.reports.members') }}">
                    <i class="bi bi-person-check"></i> Member Summary
                </a>
            </li>
        @else
            <li class="nav-section">My Account</li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('member.dashboard') ? 'active' : '' }}"
                   href="{{ route('member.dashboard') }}">
                    <i class="bi bi-house"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('member.payments') ? 'active' : '' }}"
                   href="{{ route('member.payments') }}">
                    <i class="bi bi-receipt"></i> Payment History
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('member.profile') ? 'active' : '' }}"
                   href="{{ route('member.profile') }}">
                    <i class="bi bi-person-circle"></i> My Profile
                </a>
            </li>
        @endif
    </ul>

    <div class="position-absolute bottom-0 w-100 p-3" style="border-top:1px solid rgba(255,255,255,.1)">
        <div class="text-white-50 small mb-2">
            <i class="bi bi-person"></i> {{ auth()->user()->name }}
            <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">
                {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
            </span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-outline-light btn-sm w-100">
                <i class="bi bi-box-arrow-left"></i> Sign Out
            </button>
        </form>
    </div>
</nav>

<!-- Main -->
<div id="main">
    <!-- Topbar -->
    <div class="topbar d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm d-md-none" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h6 class="mb-0 fw-semibold text-muted">@yield('page-title', 'Dashboard')</h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if(auth()->user()->email && !auth()->user()->hasVerifiedEmail())
                <a href="{{ route('email.resend') }}" class="badge bg-warning text-dark text-decoration-none"
                   onclick="event.preventDefault(); document.getElementById('resend-form').submit()">
                    <i class="bi bi-envelope-exclamation"></i> Verify Email
                </a>
                <form id="resend-form" method="POST" action="{{ route('email.resend') }}" class="d-none">@csrf</form>
            @endif
            <span class="text-muted small d-none d-md-inline">{{ auth()->user()->name }}</span>
        </div>
    </div>

    <!-- Alerts -->
    <div class="px-3 pt-3">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    </div>

    <div class="page-content">
        @yield('content')
    </div>
</div>

@endauth

@guest
    @yield('content')
@endguest

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('show');
    });
</script>
@stack('scripts')
</body>
</html>
