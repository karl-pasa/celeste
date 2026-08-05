@extends('layouts.public')

@section('title', 'Scan a QR code')

@section('content')
<div class="row justify-content-center pt-4">
    <div class="col-lg-6 col-xl-5 text-center mb-4">
        <h1 class="text-white fw-bold mb-2" style="font-size:1.75rem">Scan the QR code</h1>
        <p class="mb-0" style="color:rgba(255,255,255,.75);font-size:.9375rem">
            The code sits in the lower corner of every CELESTE document. Hold it steady inside the frame.
        </p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6 col-xl-5">
        @livewire('verification.verify-panel')
    </div>
</div>

<div class="row justify-content-center mt-4">
    <div class="col-lg-6 col-xl-5">
        <div class="p-3 rounded-3" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1)">
            <h6 class="text-white mb-2" style="font-size:.875rem"><i class="bi bi-lightbulb"></i> If the code will not read</h6>
            <ul class="mb-0 ps-3" style="color:rgba(255,255,255,.68);font-size:.8125rem;line-height:1.7">
                <li>Move into brighter light and hold the document flat.</li>
                <li>Photocopies fade the code — try the original.</li>
                <li>Type the serial number printed below the code instead.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
