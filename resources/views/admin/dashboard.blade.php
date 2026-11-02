@extends('layouts.app')

@section('title', 'Administration')
@section('subtitle', 'Student data and sign-ins')

@section('content')
    <div class="row g-3 mb-4">
        @foreach ([
            ['Student records',        $records,      'bi-people',        'secondary'],
            ['Student logins',         $logins,       'bi-person-check',  'success'],
            ['Logins switched on',     $active,       'bi-toggle-on',     'success'],
            ['Students without login', $withoutLogin, 'bi-person-dash',   $withoutLogin > 0 ? 'warning' : 'secondary'],
            ['Registrar accounts',     $registrars,   'bi-shield-lock',   'secondary'],
            ['Documents issued',       $certificates, 'bi-file-earmark-check', 'secondary'],
        ] as [$label, $value, $icon, $tone])
            <div class="col-6 col-lg-4">
                <div class="card h-100 border-{{ $tone }}">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi {{ $icon }} fs-2 text-{{ $tone }}"></i>
                        <div>
                            <div class="fs-3 fw-semibold lh-1">{{ number_format($value) }}</div>
                            <div class="small text-muted-celeste">{{ $label }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.logins') }}" class="btn btn-psu"><i class="bi bi-person-lock"></i> Manage student logins</a>
        <a href="{{ route('admin.students') }}" class="btn btn-psu-outline"><i class="bi bi-people"></i> Student records</a>
    </div>
@endsection