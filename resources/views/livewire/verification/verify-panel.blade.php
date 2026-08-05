<div x-data="{ mode: 'type' }">
    <div class="card-celeste p-3 p-md-4">

        {{-- Mode switch --}}
        <div class="role-tabs mb-3">
            <button type="button" class="role-tab" :class="{ 'active': mode === 'type' }" @click="mode = 'type'; $dispatch('stop-scanner')">
                <i class="bi bi-keyboard"></i> Enter serial
            </button>
            <button type="button" class="role-tab" :class="{ 'active': mode === 'scan' }" @click="mode = 'scan'; $nextTick(() => $dispatch('start-scanner'))">
                <i class="bi bi-qr-code-scan"></i> Scan QR code
            </button>
        </div>

        {{-- Typed lookup --}}
        <div x-show="mode === 'type'">
            <form wire:submit="verify">
                <label for="reference" class="form-label">
                    <i class="bi bi-upc-scan"></i> Serial number or fingerprint
                </label>
                <input type="text" id="reference" wire:model="reference"
                       class="form-control @error('reference') is-invalid @enderror"
                       placeholder="PSU-DIP-2026-000184"
                       autocomplete="off" spellcheck="false">
                @error('reference')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <p class="text-muted-celeste mt-2 mb-3" style="font-size:.75rem">
                    The serial is printed under the QR code. You can also paste the 64-character fingerprint
                    or the full verification link.
                </p>

                <button type="submit" class="btn btn-psu w-100" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="verify"><i class="bi bi-search"></i> Verify document</span>
                    <span wire:loading wire:target="verify">
                        <span class="spinner-border spinner-border-sm me-1"></span> Checking the record…
                    </span>
                </button>
            </form>
        </div>

        {{-- Camera scanner --}}
        <div x-show="mode === 'scan'" x-cloak>
            <div class="scanner-frame mb-3">
                <div id="celeste-reader" class="w-100 h-100"></div>
                <div class="scanner-reticle"></div>
            </div>
            <p class="text-center text-muted-celeste mb-0" style="font-size:.8125rem">
                Hold the QR code inside the frame. Allow camera access when your browser asks.
            </p>
            <p id="scanner-error" class="text-center mb-0 mt-2 d-none" style="font-size:.8125rem;color:var(--psu-red)"></p>
        </div>
    </div>

    {{-- Result --}}
    @if ($outcome)
        <div class="result-card mt-3" wire:key="result-{{ $outcome['result'] }}-{{ now()->timestamp }}">
            <div class="result-banner result-{{ $outcome['result'] }}">
                <div class="result-icon">
                    <i class="bi {{ match ($outcome['result']) {
                        'authentic' => 'bi-patch-check-fill',
                        'revoked'   => 'bi-slash-circle',
                        'tampered'  => 'bi-exclamation-octagon-fill',
                        default     => 'bi-question-circle',
                    } }}"></i>
                </div>
                <h2>{{ match ($outcome['result']) {
                    'authentic' => 'Authentic document',
                    'revoked'   => 'No longer valid',
                    'tampered'  => 'Does not match our records',
                    default     => 'Not on file',
                } }}</h2>
                <p>{{ $outcome['message'] }}</p>
            </div>

            @if ($outcome['record'])
                <div class="p-3 p-md-4">
                    <dl class="mb-0">
                        <div class="detail-row"><dt>Document</dt><dd>{{ $outcome['record']['type'] }}</dd></div>
                        <div class="detail-row"><dt>Serial number</dt><dd class="serial">{{ $outcome['record']['serial'] }}</dd></div>
                        <div class="detail-row"><dt>Issued to</dt><dd>{{ $outcome['record']['holder'] }}</dd></div>
                        <div class="detail-row"><dt>Program</dt><dd>{{ $outcome['record']['program'] }}</dd></div>
                        <div class="detail-row"><dt>College</dt><dd>{{ $outcome['record']['college'] }}</dd></div>
                        <div class="detail-row"><dt>Date issued</dt><dd>{{ $outcome['record']['issued_on'] }}</dd></div>
                        <div class="detail-row"><dt>Fingerprint</dt><dd><span class="hash-chip">{{ $outcome['record']['hash'] }}</span></dd></div>
                        @if ($outcome['record']['reason'])
                            <div class="detail-row"><dt>Reason</dt><dd>{{ $outcome['record']['reason'] }}</dd></div>
                        @endif
                        <div class="detail-row"><dt>Times verified</dt><dd>{{ number_format($outcome['record']['checks']) }}</dd></div>
                    </dl>

                    <button wire:click="reset_check" class="btn btn-psu-outline w-100 mt-3">
                        <i class="bi bi-arrow-repeat"></i> Check another document
                    </button>
                </div>
            @else
                <div class="p-3 p-md-4">
                    <p class="text-muted-celeste mb-3" style="font-size:.875rem">
                        Reference checked: <span class="hash-chip">{{ $reference }}</span>
                    </p>
                    <button wire:click="reset_check" class="btn btn-psu-outline w-100">
                        <i class="bi bi-arrow-repeat"></i> Try another reference
                    </button>
                </div>
            @endif
        </div>
    @endif

    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                let scanner = null;
                const errorBox = document.getElementById('scanner-error');

                const showError = (message) => {
                    if (!errorBox) return;
                    errorBox.textContent = message;
                    errorBox.classList.remove('d-none');
                };

                window.addEventListener('start-scanner', () => {
                    if (scanner) return;

                    scanner = new Html5Qrcode('celeste-reader');
                    scanner.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 220, height: 220 } },
                        (decodedText) => {
                            scanner.stop().then(() => { scanner = null; });
                            // Hand the decoded value to the Livewire component.
                            Livewire.dispatch('qr-scanned', { value: decodedText });
                        },
                        () => { /* per-frame misses are normal; stay quiet */ }
                    ).catch(() => {
                        showError('Camera unavailable. Check browser permissions, or enter the serial number instead.');
                    });
                });

                window.addEventListener('stop-scanner', () => {
                    if (scanner) {
                        scanner.stop().then(() => { scanner = null; }).catch(() => { scanner = null; });
                    }
                });
            });
        </script>
    @endpush
</div>
