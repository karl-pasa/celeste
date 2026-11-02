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
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
    <link rel="icon" type="image/png" sizes="any" href="{{ asset('favicon-96x96.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <style>
        /* The profile and Sign out block sits directly under the last menu
           link, with no empty gap, and always stays on screen. If a very short
           screen cannot hold everything, the menu scrolls by wheel or touch
           with no scroll bar drawn. */
        .sidebar { display: flex; flex-direction: column; height: 100vh; height: 100dvh; max-height: 100dvh; overflow: hidden; }
        .sidebar .brand { flex: 0 0 auto; }
        .sidebar nav { flex: 0 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; scrollbar-width: none; -ms-overflow-style: none; }
        .sidebar nav::-webkit-scrollbar { display: none; }
        .sidebar .sidebar-foot { flex: 0 0 auto; margin-top: 0; }

        /* A little tighter only when the screen is short. */
        @media (max-height: 760px) {
            .sidebar .brand { padding-top: 1rem !important; padding-bottom: 1rem !important; }
            .sidebar .brand img { width: 52px !important; height: 52px !important; flex-basis: 52px !important; }
            .sidebar .nav-section { padding-top: .75rem !important; }
            .sidebar .nav-link { padding-top: .45rem !important; padding-bottom: .45rem !important; }
        }
    </style>
    @livewireStyles
</head>
<body>
<div class="app-shell" x-data="{ nav: false }">

    <aside class="sidebar" :class="{ 'open': nav }">
        <div class="brand" style="gap:.75rem;padding-top:1.25rem;padding-bottom:1.25rem">
            <img src="{{ asset('images/psu-seal-white.png') }}" alt="Partido State University seal"
                 style="width:60px;height:60px;flex:0 0 60px;object-fit:contain"
                 onerror="this.outerHTML='&lt;div class=&quot;mark&quot;&gt;CE&lt;/div&gt;'">
            <div style="min-width:0">
                <div class="name" style="font-size:1.25rem;line-height:1.15">CELESTE</div>
                <div class="sub" style="font-size:.75rem;line-height:1.25">{{ config('celeste.institution.short') }}</div>
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
                <a href="{{ route('registrar.logins') }}" class="nav-link {{ request()->routeIs('registrar.logins') ? 'active' : '' }}">
                    <i class="bi bi-person-lock"></i> Student logins
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
            {{-- Asks first; the form itself is inside the confirmation box below. --}}
            <button type="button" class="btn btn-sm w-100" style="background:rgba(255,255,255,.1); color:#fff; border:0"
                    data-bs-toggle="modal" data-bs-target="#signOutModal">
                <i class="bi bi-box-arrow-right"></i> Sign out
            </button>
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
            <div class="d-flex align-items-center gap-3">
                @yield('actions')

                {{-- Live date and time, Philippine time, on every page. The
                     server renders the first value so nothing flashes blank;
                     the script at the bottom updates it every second. --}}
                <div class="text-end lh-sm d-none d-sm-block">
                    <div id="clockTime" class="fw-semibold" style="font-size:1.05rem">{{ now('Asia/Manila')->format('g:i:s A') }}</div>
                    <div id="clockDate" class="text-muted-celeste" style="font-size:.75rem">{{ now('Asia/Manila')->format('l, F j, Y') }}</div>
                </div>
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

<div class="modal fade" id="signOutModal" tabindex="-1" aria-labelledby="signOutTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px">
        <div class="modal-content">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <div class="modal-body" style="padding:1.25rem 1.5rem .75rem">
                    <h6 class="fw-semibold mb-2" id="signOutTitle">Sign out</h6>
                    <p class="small mb-0">Do you want to sign out of CELESTE?</p>
                </div>
                <div class="modal-footer border-0 justify-content-start gap-2" style="padding:.75rem 1.5rem 1.25rem">
                    {{-- Red Sign out on the left; plain Cancel on the right. --}}
                    <button type="submit" class="btn btn-sm px-3"
                            style="background:var(--psu-red, #c9354a);border-color:var(--psu-red, #c9354a);color:#fff">
                        Sign out
                    </button>
                    <button type="button" class="btn btn-sm px-3" data-bs-dismiss="modal"
                            style="background:transparent;border:0;box-shadow:none;color:inherit">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@livewireScripts
<script>
    (function () {
        const time = document.getElementById('clockTime');
        const date = document.getElementById('clockDate');
        if (!time || !date) return;
        const timeFmt = new Intl.DateTimeFormat('en-US', {
            timeZone: 'Asia/Manila', hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true,
        });
        const dateFmt = new Intl.DateTimeFormat('en-US', {
            timeZone: 'Asia/Manila', weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
        });
        function tick() {
            const now = new Date();
            time.textContent = timeFmt.format(now);
            date.textContent = dateFmt.format(now);
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
<script>
    // Leaving the website for more than 30 seconds signs the user out.
    // While this page is in front it tells the server every 10 seconds that
    // someone is still here. When the page is hidden (another tab, minimised,
    // closed) it stops, and the server ends the session once 30 seconds pass.
    (function () {
        const LIMIT = 30000, BEAT = 10000;
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const pingUrl = @json(route('session.ping'));
        const logoutUrl = @json(route('logout'));
        const loginUrl = @json(route('login', ['timeout' => 1]));
        let beat = null, awayTimer = null, gone = false;

        const body = () => { const f = new FormData(); f.append('_token', token); return f; };
        const toLogin = () => { if (!gone) { gone = true; window.location.replace(loginUrl); } };

        async function ping() {
            try {
                const res = await fetch(pingUrl, {
                    method: 'POST', body: body(), credentials: 'same-origin', cache: 'no-store',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.status === 401 || res.status === 419) toLogin();
            } catch (e) { /* offline for a moment: the next one tries again */ }
        }

        function away() {
            clearInterval(beat); beat = null;
            try { navigator.sendBeacon(pingUrl, body()); } catch (e) {}   // marks the moment of leaving
            clearTimeout(awayTimer);
            awayTimer = setTimeout(() => {
                fetch(logoutUrl, { method: 'POST', body: body(), credentials: 'same-origin', keepalive: true }).catch(() => {});
            }, LIMIT);
        }

        function back() {
            clearTimeout(awayTimer);
            ping();
            if (!beat) beat = setInterval(() => { if (!document.hidden) ping(); }, BEAT);
        }

        document.addEventListener('visibilitychange', () => (document.hidden ? away() : back()));
        window.addEventListener('pagehide', () => { try { navigator.sendBeacon(pingUrl, body()); } catch (e) {} });
        if (!document.hidden) back();
    })();
</script>
@stack('scripts')
</body>
</html>