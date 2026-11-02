@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Student data and sign-ins · ' . config('celeste.institution.short'))

@section('content')
{{-- Same look as the Registrar dashboard: coloured boxes on top, plain boxes below. --}}
<div class="row g-3 mb-3" data-live="types">
    @foreach ([
        ['Student records',        $records,      'bi-people',       'var(--psu-blue)',  'var(--psu-blue-soft)'],
        ['Student logins',         $logins,       'bi-person-check', 'var(--psu-green)', 'var(--psu-green-soft)'],
        ['Students without login', $withoutLogin, 'bi-person-dash',  'var(--psu-gold)',  'var(--psu-gold-soft)'],
        ['Registrar accounts',     $registrars,   'bi-shield-lock',  'var(--psu-red)',   'var(--psu-red-soft)'],
    ] as [$label, $value, $icon, $strong, $soft])
        <div class="col-6 col-xl-3">
            <div class="stat" style="border-top:3px solid {{ $strong }};background:{{ $soft }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="stat-label" style="color:{{ $strong }}">{{ $label }}</div>
                    <i class="bi {{ $icon }}" style="color:{{ $strong }};font-size:1.1rem"></i>
                </div>
                <div class="stat-value" style="color:{{ $strong }}">{{ number_format($value) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-3" data-live="stats">
    @foreach ([
        ['Logins switched on',    number_format($active),        number_format($switchedOff) . ' switched off', 'bi-toggle-on'],
        ['Login coverage',        ($records > 0 ? round($logins / $records * 100) : 0) . '%', number_format($logins) . ' of ' . number_format($records) . ' students', 'bi-person-lines-fill'],
        ['Documents issued',      number_format($certificates),  null,                                          'bi-collection'],
        ['Verifications (30d)',   number_format($verifications), null,                                          'bi-search'],
    ] as [$label, $value, $meta, $icon])
        <div class="col-6 col-xl-3">
            <div class="stat">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="stat-label">{{ $label }}</div>
                    <i class="bi {{ $icon }} text-muted-celeste"></i>
                </div>
                <div class="stat-value">{{ $value }}</div>
                @if ($meta)
                    <div class="stat-meta">{{ $meta }}</div>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
    // Same quiet refresh as the Registrar dashboard: every 10 seconds the
    // numbers are updated in place, without reloading the page.
    (function () {
        let busy = false;
        async function refresh() {
            if (busy || document.hidden) return;
            busy = true;
            try {
                const res = await fetch(window.location.href, {
                    credentials: 'same-origin', cache: 'no-store',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!res.ok) return;
                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                document.querySelectorAll('[data-live]').forEach((el) => {
                    const fresh = doc.querySelector('[data-live="' + el.dataset.live + '"]');
                    if (fresh && fresh.innerHTML !== el.innerHTML) el.innerHTML = fresh.innerHTML;
                });
            } catch (e) { /* the next refresh tries again */ }
            finally { busy = false; }
        }
        setInterval(refresh, 10000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    })();
</script>
@endpush