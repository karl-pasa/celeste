<?php $__env->startSection('title', $certificate->serial_number); ?>
<?php $__env->startSection('subtitle', $certificate->type_label . ' · issued ' . $certificate->issued_on?->format('F j, Y')); ?>

<?php $__env->startSection('actions'); ?>
    <a href="<?php echo e(route('certificates.print', $certificate)); ?>" target="_blank" class="btn btn-sm btn-psu-outline">
        <i class="bi bi-printer"></i> Print
    </a>
    <a href="<?php echo e(route('certificates.download', $certificate)); ?>" class="btn btn-sm btn-psu">
        <i class="bi bi-download"></i> Download
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-3">
    <div class="col-lg-8">
        
        <div class="card-celeste mb-3">
            <div class="p-3 d-flex align-items-center gap-3"
                 style="border-left:3px solid <?php echo e($hashIntact ? 'var(--psu-green)' : 'var(--psu-red)'); ?>;border-radius:var(--radius-lg)">
                <i class="bi <?php echo e($hashIntact ? 'bi-shield-check' : 'bi-shield-exclamation'); ?>"
                   style="font-size:1.5rem;color:<?php echo e($hashIntact ? 'var(--psu-green)' : 'var(--psu-red)'); ?>"></i>
                <div>
                    <h6 class="mb-1"><?php echo e($hashIntact ? 'Fingerprint intact' : 'Fingerprint mismatch'); ?></h6>
                    <p class="mb-0 text-muted-celeste" style="font-size:.8125rem">
                        <?php echo e($hashIntact
                            ? 'The stored record still hashes to the value issued with this document.'
                            : 'The stored record no longer matches its original fingerprint. This document will fail public verification — investigate before reissuing.'); ?>

                    </p>
                </div>
            </div>
        </div>

        <div class="card-celeste mb-3">
            <div class="card-header">Document record</div>
            <div class="p-3 p-md-4">
                <dl class="mb-0">
                    <div class="detail-row"><dt>Serial number</dt><dd class="serial"><?php echo e($certificate->serial_number); ?></dd></div>
                    <div class="detail-row"><dt>Document type</dt><dd><?php echo e($certificate->type_label); ?></dd></div>
                    <div class="detail-row"><dt>Status</dt><dd>
                        <span class="badge-celeste <?php echo e(match ($certificate->status) {
                            'issued' => 'badge-issued', 'revoked' => 'badge-revoked', default => 'badge-superseded',
                        }); ?>"><?php echo e(ucfirst($certificate->status)); ?></span>
                    </dd></div>
                    <div class="detail-row"><dt>Issued to</dt><dd><?php echo e($certificate->studentRecord?->full_name); ?></dd></div>
                    <div class="detail-row"><dt>Student number</dt><dd class="serial"><?php echo e($certificate->studentRecord?->student_number); ?></dd></div>
                    <div class="detail-row"><dt>Program</dt><dd><?php echo e($certificate->studentRecord?->program); ?></dd></div>
                    <div class="detail-row"><dt>College</dt><dd><?php echo e($certificate->studentRecord?->college); ?></dd></div>
                    <div class="detail-row"><dt>Issued by</dt><dd><?php echo e($certificate->issuer?->name); ?></dd></div>
                    <div class="detail-row"><dt>Generated</dt><dd><?php echo e($certificate->created_at->format('M j, Y g:i A')); ?></dd></div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->batch): ?>
                        <div class="detail-row"><dt>Batch</dt><dd><?php echo e($certificate->batch->reference); ?> — <?php echo e($certificate->batch->label); ?></dd></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->revocation_reason): ?>
                        <div class="detail-row"><dt>Reason</dt><dd><?php echo e($certificate->revocation_reason); ?></dd></div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="detail-row"><dt>Times verified</dt><dd><?php echo e(number_format($certificate->verification_count)); ?></dd></div>
                    <div class="detail-row"><dt>Last verified</dt><dd><?php echo e($certificate->last_verified_at?->diffForHumans() ?? 'Never'); ?></dd></div>
                </dl>

                <div class="mt-3">
                    <div class="form-label">Content fingerprint (SHA-256)</div>
                    <div class="hash-chip d-block mb-2"><?php echo e($certificate->content_hash); ?></div>
                    <div class="form-label">File fingerprint</div>
                    <div class="hash-chip d-block"><?php echo e($certificate->file_hash ?? 'Not yet rendered'); ?></div>
                </div>
            </div>
        </div>

        <div class="card-celeste">
            <div class="card-header">Verification history</div>
            <div class="table-responsive">
                <table class="table table-celeste">
                    <thead>
                        <tr><th>Result</th><th>Method</th><th>IP</th><th>When</th></tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $certificate->verificationLogs()->latest()->limit(20)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td><span class="badge-celeste <?php echo e($log->resultBadge()); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $log->result))); ?></span></td>
                                <td class="text-muted-celeste"><?php echo e(\App\Models\VerificationLog::methods()[$log->method] ?? $log->method); ?></td>
                                <td class="text-muted-celeste"><?php echo e($log->ip_address); ?></td>
                                <td class="text-muted-celeste"><?php echo e($log->created_at->format('M j, Y g:i A')); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="4">
                                <div class="empty">
                                    <div class="empty-icon"><i class="bi bi-clock-history"></i></div>
                                    <h6>Not verified yet</h6>
                                    <p>Checks against this document will be listed here.</p>
                                </div>
                            </td></tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-celeste mb-3">
            <div class="card-header">Verification QR</div>
            <div class="p-3 p-md-4 text-center">
                <img src="<?php echo e($qr); ?>" alt="QR code for <?php echo e($certificate->serial_number); ?>" class="img-fluid mb-3" style="max-width:200px">
                <p class="text-muted-celeste mb-2" style="font-size:.8125rem">This code is printed on the document.</p>
                <div class="hash-chip d-block mb-3" style="word-break:break-all"><?php echo e($certificate->verificationUrl()); ?></div>
                <a href="<?php echo e($certificate->verificationUrl()); ?>" target="_blank" class="btn btn-psu-outline btn-sm w-100">
                    <i class="bi bi-box-arrow-up-right"></i> Open the public result
                </a>
                <a href="<?php echo e(route('certificates.qr', $certificate)); ?>" download class="btn btn-psu-outline btn-sm w-100 mt-2">
                    <i class="bi bi-download"></i> Download the QR image
                </a>
            </div>
        </div>

        <div class="card-celeste">
            <div class="card-header">Hashed payload</div>
            <div class="p-3">
                <p class="text-muted-celeste mb-2" style="font-size:.8125rem">
                    These are the exact values the fingerprint covers.
                </p>
                <dl class="mb-0">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $certificate->payload; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if(is_array($value)) continue; ?>
                        <div class="detail-row">
                            <dt style="font-size:.8125rem"><?php echo e(ucfirst(str_replace('_', ' ', $key))); ?></dt>
                            <dd style="font-size:.8125rem"><?php echo e($value ?: '—'); ?></dd>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/registrar/certificate-show.blade.php ENDPATH**/ ?>