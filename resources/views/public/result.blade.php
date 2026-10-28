@extends('layouts.public')

@section('title', 'Verification result')

@php
    $certificate = $outcome['certificate'];
    $result = $outcome['result'];
    $heading = match ($result) {
        'authentic' => 'Authentic document',
        'revoked'   => 'No longer valid',
        'tampered'  => 'Does not match our records',
        default     => 'Not on file',
    };
    $icon = match ($result) {
        'authentic' => 'bi-patch-check-fill',
        'revoked'   => 'bi-slash-circle',
        'tampered'  => 'bi-exclamation-octagon-fill',
        default     => 'bi-question-circle',
    };
@endphp

@section('content')
<div class="row justify-content-center pt-4">
    <div class="col-lg-7 col-xl-6">
        <div class="result-card">
            <div class="result-banner result-{{ $result }}">
                <div class="result-icon"><i class="bi {{ $icon }}"></i></div>
                <h2>{{ $heading }}</h2>
                <p>{{ $outcome['message'] }}</p>
            </div>

            @if ($certificate)
                <div class="p-3 p-md-4">
                    <dl class="mb-0">
                        <div class="detail-row"><dt>Document</dt><dd>{{ $certificate->type_label }}</dd></div>
                        <div class="detail-row"><dt>Serial number</dt><dd class="serial">{{ $certificate->serial_number }}</dd></div>
                        <div class="detail-row"><dt>Issued to</dt><dd>{{ $certificate->studentRecord?->full_name }}</dd></div>
                        <div class="detail-row"><dt>Student number</dt><dd class="serial">{{ $certificate->studentRecord?->student_number }}</dd></div>
                        <div class="detail-row"><dt>Program</dt><dd>{{ $certificate->studentRecord?->program }}</dd></div>
                        <div class="detail-row"><dt>College</dt><dd>{{ $certificate->studentRecord?->college }}</dd></div>
                        <div class="detail-row"><dt>Date issued</dt><dd>{{ $certificate->issued_on?->format('F j, Y') }}</dd></div>
                        <div class="detail-row"><dt>Issued by</dt><dd>{{ config('celeste.institution.name') }}</dd></div>
                        <div class="detail-row"><dt>Fingerprint</dt><dd><span class="hash-chip">{{ $certificate->shortHash() }}</span></dd></div>
                        @if ($certificate->revocation_reason)
                            <div class="detail-row"><dt>Reason</dt><dd>{{ $certificate->revocation_reason }}</dd></div>
                        @endif
                    </dl>
                </div>
                @if ($result === 'authentic' && $certificate->verification_token)
                    {{-- Only shown for a document that checks out. The viewer
                         is the issued file itself, so the verifier can compare
                         it with the paper copy in their hands. --}}
                    <div class="px-3 px-md-4 pb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0" style="font-size:.875rem"><i class="bi bi-file-earmark-text"></i> Issued document</h6>
                            <a href="{{ route('verify.preview', $certificate->verification_token) }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-psu-outline">
                                <i class="bi bi-box-arrow-up-right"></i> Open full size
                            </a>
                        </div>
                        {{-- Drawn with PDF.js rather than a browser frame: phone
                             browsers (Android Chrome, iOS Safari) will not show a
                             PDF inside a frame and offer a bare "Open" button
                             instead. Each page is painted onto a canvas, which
                             works on every device. --}}
                        <div id="pdfPages"
                             data-src="{{ route('verify.preview', $certificate->verification_token) }}"
                             style="max-height:70vh;overflow:auto;border:1px solid #e3e8f1;border-radius:.5rem;background:#f4f6fa;padding:.5rem">
                            <p id="pdfStatus" class="text-muted-celeste text-center my-4" style="font-size:.8125rem">
                                Loading document…
                            </p>
                        </div>
                        <p class="text-muted-celeste mt-2 mb-0" style="font-size:.75rem">
                            Compare this with the copy you were given. Use “Open full size”
                            to see it larger.
                        </p>
                    </div>
                @endif
            @else
                <div class="p-3 p-md-4">
                    <p class="text-muted-celeste mb-0" style="font-size:.875rem">
                        Reference checked: <span class="hash-chip">{{ $reference }}</span>
                    </p>
                </div>
            @endif

            <div class="p-3 p-md-4 pt-0 d-flex gap-2 flex-wrap">
                <a href="{{ route('verify') }}" class="btn btn-psu flex-fill">
                    <i class="bi bi-arrow-repeat"></i> Check another document
                </a>
                <a href="{{ route('verify.scanner') }}" class="btn btn-psu-outline flex-fill">
                    <i class="bi bi-qr-code-scan"></i> Scan a code
                </a>
            </div>
        </div>

        @if (in_array($result, ['tampered', 'revoked'], true))
            <div class="mt-3 p-3 rounded-3" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1)">
                <h6 class="text-white mb-1" style="font-size:.875rem"><i class="bi bi-exclamation-triangle"></i> What to do next</h6>
                <p class="mb-0" style="color:rgba(255,255,255,.7);font-size:.8125rem">
                    Do not accept this copy as proof. Report it to the Office of the University Registrar at
                    {{ config('celeste.institution.registrar_email') }}, quoting the reference above.
                </p>
            </div>
        @endif
    </div>
</div>

@if ($result === 'authentic' && $certificate?->verification_token)
<script>
    (function () {
        const box = document.getElementById('pdfPages');
        if (!box) return;
        const status = document.getElementById('pdfStatus');
        const CDN = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/';

        function fail() {
            status.innerHTML = 'The preview could not be shown here. Use “Open full size” above.';
        }

        const s = document.createElement('script');
        s.src = CDN + 'pdf.min.js';
        s.onerror = fail;
        s.onload = async function () {
            try {
                pdfjsLib.GlobalWorkerOptions.workerSrc = CDN + 'pdf.worker.min.js';
                const pdf = await pdfjsLib.getDocument(box.dataset.src).promise;
                status.remove();

                const width = box.clientWidth - 18;
                const ratio = window.devicePixelRatio || 1;

                for (let n = 1; n <= pdf.numPages; n++) {
                    const page = await pdf.getPage(n);
                    const base = page.getViewport({ scale: 1 });
                    const scale = width / base.width;
                    const vp = page.getViewport({ scale: scale * ratio });

                    const canvas = document.createElement('canvas');
                    canvas.width = vp.width;
                    canvas.height = vp.height;
                    canvas.style.cssText = 'display:block;margin:0 auto .5rem;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.15);'
                        + 'width:' + width + 'px;height:' + (vp.height / ratio) + 'px';
                    box.appendChild(canvas);

                    await page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise;
                }
            } catch (e) {
                fail();
            }
        };
        document.head.appendChild(s);
    })();
</script>
@endif
@endsection