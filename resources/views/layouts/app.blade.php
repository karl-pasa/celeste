<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — CELESTE</title>

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/celeste.css') }}" rel="stylesheet">
    @livewireStyles
</head>
<body>
<div class="app-shell" x-data="{ nav: false }">

    <aside class="sidebar" :class="{ 'open': nav }">
        <div class="brand">
            <img src="{{ asset('images/psu-seal-white.png') }}" alt=""
                 style="width:34px;height:34px;object-fit:contain"
                 onerror="this.outerHTML='&lt;div class=&quot;mark&quot;&gt;CE&lt;/div&gt;'">
            <div>
                <div class="name">CELESTE</div>
                <div class="sub">{{ config('celeste.institution.short') }}</div>
            </div>
        </div>

        <nav class="mt-1">
            @if (auth()->user()->isRegistrar())
                <div class="nav-section">Overview</div>
                <a href="{{ route('registrar.dashboard') }}" class="nav-link {{ request()->routeIs('registrar.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2"></i> Dashboard
                </a>

                <div class="nav-section">Documents</div>
                <a href="{{ route('registrar.certificates.generate') }}" class="nav-link {{ request()->routeIs('registrar.certificates.generate') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-plus"></i> Generate a document
                </a>
                <a href="{{ route('registrar.certificates.batch') }}" class="nav-link {{ request()->routeIs('registrar.certificates.batch') ? 'active' : '' }}">
                    <i class="bi bi-files"></i> Batch generation
                </a>
                <a href="{{ route('registrar.certificates') }}" class="nav-link {{ request()->routeIs('registrar.certificates') || request()->routeIs('registrar.certificates.show') ? 'active' : '' }}">
                    <i class="bi bi-collection"></i> All certificates
                </a>
                <a href="{{ route('registrar.students') }}" class="nav-link {{ request()->routeIs('registrar.students') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Student records
                </a>

                <div class="nav-section">Verification</div>
                <a href="{{ route('registrar.analytics') }}" class="nav-link {{ request()->routeIs('registrar.analytics') ? 'active' : '' }}">
                    <i class="bi bi-graph-up-arrow"></i> Analytics
                </a>
                <a href="{{ route('registrar.logs') }}" class="nav-link {{ request()->routeIs('registrar.logs') ? 'active' : '' }}">
                    <i class="bi bi-journal-text"></i> Audit trail
                </a>
                <a href="{{ route('verify') }}" class="nav-link" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i> Public portal
                </a>
            @else
                <div class="nav-section">My records</div>
                <a href="{{ route('student.dashboard') }}" class="nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2"></i> Overview
                </a>

                <div class="nav-section">Tools</div>
                <a href="{{ route('verify.scanner') }}" class="nav-link">
                    <i class="bi bi-qr-code-scan"></i> Scan a QR code
                </a>
                <a href="{{ route('verify') }}" class="nav-link">
                    <i class="bi bi-patch-check"></i> Verify a document
                </a>
            @endif
        </nav>

        <div class="sidebar-foot">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar">{{ auth()->user()->initials() }}</div>
                <div class="small" style="min-width:0">
                    <div class="text-white text-truncate">{{ auth()->user()->name }}</div>
                    <div style="color:rgba(255,255,255,.55); font-size:.75rem">{{ auth()->user()->roleLabel() }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-sm w-100" style="background:rgba(255,255,255,.1); color:#fff; border:0">
                    <i class="bi bi-box-arrow-right"></i> Sign out
                </button>
            </form>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-psu-outline d-lg-none" @click="nav = !nav" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="page-title">@yield('title', 'Dashboard')</h1>
                    <p class="page-sub">@yield('subtitle', '')</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @yield('actions')
            </div>
        </header>

        <div class="app-content">
            @if (session('status'))
                <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 small">
                    <i class="bi bi-check-circle"></i> {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@livewireScripts
@stack('scripts')
</body>
</html>
