<div>
    <div class="card-celeste">
        <div class="p-3 border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="search" id="search" wire:model.live.debounce.300ms="search" class="form-control"
                           placeholder="Serial, fingerprint, name, or student number">
                </div>
                <div class="col-6 col-lg-3">
                    <label for="typeFilter" class="form-label">Document</label>
                    <select id="typeFilter" wire:model.live="type" class="form-select">
                        <option value="">All documents</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="statusFilter" class="form-label">Status</label>
                    <select id="statusFilter" wire:model.live="status" class="form-select">
                        <option value="">Any</option>
                        <option value="issued">Issued</option>
                        <option value="revoked">Revoked</option>
                        <option value="superseded">Superseded</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label for="yearFilter" class="form-label">Year</label>
                    <select id="yearFilter" wire:model.live="year" class="form-select">
                        <option value="">All years</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e((int) $option); ?>"><?php echo e((int) $option); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                </div>
                <div class="col-6 col-lg-1">
                    <button wire:click="clearFilters" class="btn btn-psu-outline w-100" title="Clear filters">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-celeste">
                <thead>
                    <tr>
                        <th role="button" wire:click="sortBy('serial_number')">
                            Serial
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sort === 'serial_number'): ?> <i class="bi bi-caret-<?php echo e($direction === 'asc' ? 'up' : 'down'); ?>-fill"></i> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </th>
                        <th>Document</th>
                        <th>Issued to</th>
                        <th role="button" wire:click="sortBy('issued_on')">
                            Issued
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($sort === 'issued_on'): ?> <i class="bi bi-caret-<?php echo e($direction === 'asc' ? 'up' : 'down'); ?>-fill"></i> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </th>
                        <th role="button" wire:click="sortBy('verification_count')">Checks</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $certificates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $certificate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr wire:key="cert-<?php echo e($certificate->id); ?>">
                            <td>
                                <div class="serial"><?php echo e($certificate->serial_number); ?></div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($integrity[$certificate->id])): ?>
                                    <span class="badge-celeste badge-tampered mt-1">
                                        <i class="bi bi-exclamation-octagon"></i> Fingerprint mismatch
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td><span class="badge-celeste badge-type"><?php echo e($certificate->type_label); ?></span></td>
                            <td>
                                <div><?php echo e($certificate->studentRecord?->full_name); ?></div>
                                <div class="text-muted-celeste" style="font-size:.75rem"><?php echo e($certificate->studentRecord?->student_number); ?></div>
                            </td>
                            <td class="text-muted-celeste"><?php echo e($certificate->issued_on?->format('M j, Y')); ?></td>
                            <td><?php echo e(number_format($certificate->verification_count)); ?></td>
                            <td>
                                <span class="badge-celeste <?php echo e(match ($certificate->status) {
                                    'issued' => 'badge-issued',
                                    'revoked' => 'badge-revoked',
                                    default => 'badge-superseded',
                                }); ?>"><?php echo e(ucfirst($certificate->status)); ?></span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo e(route('registrar.certificates.show', $certificate)); ?>" class="btn btn-psu-outline" title="Open">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?php echo e(route('certificates.download', $certificate)); ?>" class="btn btn-psu-outline" title="Download">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($certificate->status === 'issued'): ?>
                                        <button wire:click="confirm(<?php echo e($certificate->id); ?>, 'reissue')" class="btn btn-psu-outline" title="Reissue">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <button wire:click="confirm(<?php echo e($certificate->id); ?>, 'revoke')" class="btn btn-psu-outline" title="Revoke"
                                                style="color:var(--psu-red)">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="7">
                            <div class="empty">
                                <div class="empty-icon"><i class="bi bi-search"></i></div>
                                <h6>Nothing matches these filters</h6>
                                <p>Clear the filters, or generate a document to get started.</p>
                            </div>
                        </td></tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="p-3 border-top">
            <?php echo e($certificates->links()); ?>

        </div>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->actingCertificate): ?>
        <div class="modal d-block" tabindex="-1" style="background:rgba(10,26,60,.55)" wire:key="modal-<?php echo e($actingOn); ?>">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border:0;border-radius:var(--radius-lg)">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title">
                            <?php echo e($action === 'revoke' ? 'Revoke this document' : 'Reissue this document'); ?>

                        </h5>
                        <button type="button" class="btn-close" wire:click="cancel" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted-celeste" style="font-size:.875rem">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($action === 'revoke'): ?>
                                Anyone who verifies <span class="serial"><?php echo e($this->actingCertificate->serial_number); ?></span>
                                will be told it is void, and the reason below will be shown to them.
                                The record itself is kept.
                            <?php else: ?>
                                A replacement will be generated for
                                <?php echo e($this->actingCertificate->studentRecord?->full_name); ?> with a new serial and
                                fingerprint. <span class="serial"><?php echo e($this->actingCertificate->serial_number); ?></span>
                                will be marked superseded so old printed copies still resolve to an explanation.
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </p>

                        <label for="reason" class="form-label">Reason</label>
                        <textarea id="reason" wire:model="reason" rows="3"
                                  class="form-control <?php $__errorArgs = ['reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                  placeholder="<?php echo e($action === 'revoke' ? 'e.g. Issued against an incorrect student record' : 'e.g. Corrected spelling of the middle name'); ?>"></textarea>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['reason'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button class="btn btn-psu-outline" wire:click="cancel">Cancel</button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($action === 'revoke'): ?>
                            <button class="btn btn-psu" wire:click="revoke" style="background:var(--psu-red)">
                                <i class="bi bi-slash-circle"></i> Revoke document
                            </button>
                        <?php else: ?>
                            <button class="btn btn-psu" wire:click="reissue">
                                <i class="bi bi-arrow-repeat"></i> Reissue document
                            </button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH C:\laragon\www\celeste\resources\views/livewire/certificates/certificate-table.blade.php ENDPATH**/ ?>