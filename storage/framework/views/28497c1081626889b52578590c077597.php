<div x-data="{ mode: 'type' }">
    <div class="card-celeste p-3 p-md-4">

        
        <div class="role-tabs mb-3">
            <button type="button" class="role-tab" :class="{ 'active': mode === 'type' }" @click="mode = 'type'; $dispatch('stop-scanner')">
                <i class="bi bi-keyboard"></i> Enter serial
            </button>
            <button type="button" class="role-tab" :class="{ 'active': mode === 'scan' }" @click="mode = 'scan'; $nextTick(() => $dispatch('start-scanner'))">
                <i class="bi bi-qr-code-scan"></i> Scan QR code
            </button>
        </div>

        
        <div x-show="mode === 'type'">
            <form wire:submit="verify">
                <label for="reference" class="form-label">
                    <i class="bi bi-upc-scan"></i> Serial number or fingerprint
                </label>
                <input type="text" id="reference" wire:model="reference"
                       class="form-control <?php $__errorArgs = ['reference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       placeholder="PSU-DIP-2026-000184"
                       autocomplete="off" spellcheck="false">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['reference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($outcome): ?>
        <div class="result-card mt-3" wire:key="result-<?php echo e($outcome['result']); ?>-<?php echo e(now()->timestamp); ?>">
            <div class="result-banner result-<?php echo e($outcome['result']); ?>">
                <div class="result-icon">
                    <i class="bi <?php echo e(match ($outcome['result']) {
                        'authentic' => 'bi-patch-check-fill',
                        'revoked'   => 'bi-slash-circle',
                        'tampered'  => 'bi-exclamation-octagon-fill',
                        default     => 'bi-question-circle',
                    }); ?>"></i>
                </div>
                <h2><?php echo e(match ($outcome['result']) {
                    'authentic' => 'Authentic document',
                    'revoked'   => 'No longer valid',
                    'tampered'  => 'Does not match our records',
                    default     => 'Not on file',
                }); ?></h2>
                <p><?php echo e($outcome['message']); ?></p>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($outcome['record']): ?>
                <div class="p-3 p-md-4">
                    <dl class="mb-0">
                        <div class="detail-row"><dt>Document</dt><dd><?php echo e($outcome['record']['type']); ?></dd></div>
                        <div class="detail-row"><dt>Serial number</dt><dd class="serial"><?php echo e($outcome['record']['serial']); ?></dd></div>
                        <div class="detail-row"><dt>Issued to</dt><dd><?php echo e($outcome['record']['holder']); ?></dd></div>
                        <div class="detail-row"><dt>Program</dt><dd><?php echo e($outcome['record']['program']); ?></dd></div>
                        <div class="detail-row"><dt>College</dt><dd><?php echo e($outcome['record']['college']); ?></dd></div>
                        <div class="detail-row"><dt>Date issued</dt><dd><?php echo e($outcome['record']['issued_on']); ?></dd></div>
                        <div class="detail-row"><dt>Fingerprint</dt><dd><span class="hash-chip"><?php echo e($outcome['record']['hash']); ?></span></dd></div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($outcome['record']['reason']): ?>
                            <div class="detail-row"><dt>Reason</dt><dd><?php echo e($outcome['record']['reason']); ?></dd></div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <div class="detail-row"><dt>Times verified</dt><dd><?php echo e(number_format($outcome['record']['checks'])); ?></dd></div>
                    </dl>

                    <button wire:click="reset_check" class="btn btn-psu-outline w-100 mt-3">
                        <i class="bi bi-arrow-repeat"></i> Check another document
                    </button>
                </div>
            <?php else: ?>
                <div class="p-3 p-md-4">
                    <p class="text-muted-celeste mb-3" style="font-size:.875rem">
                        Reference checked: <span class="hash-chip"><?php echo e($reference); ?></span>
                    </p>
                    <button wire:click="reset_check" class="btn btn-psu-outline w-100">
                        <i class="bi bi-arrow-repeat"></i> Try another reference
                    </button>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php $__env->startPush('scripts'); ?>
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
    <?php $__env->stopPush(); ?>
</div>
<?php /**PATH C:\laragon\www\celeste\resources\views/livewire/verification/verify-panel.blade.php ENDPATH**/ ?>