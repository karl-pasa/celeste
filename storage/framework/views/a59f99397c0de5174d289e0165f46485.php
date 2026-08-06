<div class="row g-3">

    
    <style>
        .doc-choice {
            width: 100%;
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .85rem 1rem;
            height: 100%;
            text-align: left;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: #fff;
            font-size: .875rem;
            color: var(--ink);
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }

        .doc-choice:hover {
            border-color: var(--psu-navy-500);
            background: var(--psu-navy-050);
        }

        /* Kill the browser's blue ring. The global :focus-visible rule in
           celeste.css uses --psu-blue, which is what lingered on a card after
           it was clicked and then unselected. */
        .doc-choice:focus,
        .doc-choice:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(36, 65, 127, .18);
            border-color: var(--psu-navy-500);
        }

        .doc-choice[aria-pressed="true"] {
            border-color: var(--psu-navy-500);
            background: var(--psu-navy-050);
            color: var(--psu-navy-800);
            font-weight: 600;
            box-shadow: 0 0 0 3px rgba(36, 65, 127, .12);
        }

        /* The check mark exists only on a chosen card. Nothing is drawn in its
           place otherwise, so an unchosen option carries no marker at all. */
        .doc-choice .doc-check {
            color: var(--psu-navy-600);
            font-size: 1rem;
            flex-shrink: 0;
        }
    </style>

    <div class="col-lg-7">
        <div class="card-celeste">
            <div class="card-header">Document details</div>
            <div class="p-3 p-md-4">

                
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="step-pill">1</span>
                    <label class="form-label mb-0">Find the student record</label>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->student): ?>
                    <div class="d-flex justify-content-between align-items-center p-3 rounded-3 mb-3"
                         style="background:var(--psu-navy-050);border:1px solid var(--line)">
                        <div>
                            <div class="fw-semibold"><?php echo e($this->student->full_name); ?></div>
                            <div class="text-muted-celeste" style="font-size:.8125rem">
                                <span class="serial"><?php echo e($this->student->student_number); ?></span>
                                · <?php echo e($this->student->program); ?>

                                · <span class="text-capitalize"><?php echo e($this->student->status); ?></span>
                            </div>
                        </div>
                        <button wire:click="clearStudent" class="btn btn-sm btn-psu-outline">Change</button>
                    </div>
                <?php else: ?>
                    <input type="text" wire:model.live.debounce.300ms="search"
                           class="form-control <?php $__errorArgs = ['studentId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           placeholder="Search by name or student number" autocomplete="off">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['studentId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback d-block"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->results->isNotEmpty()): ?>
                        <div class="list-group mt-2 mb-3">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" wire:click="selectStudent(<?php echo e($record->id); ?>)"
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <span>
                                        <span class="d-block"><?php echo e($record->full_name); ?></span>
                                        <span class="text-muted-celeste" style="font-size:.8125rem">
                                            <?php echo e($record->student_number); ?> · <?php echo e($record->program); ?>

                                        </span>
                                    </span>
                                    <span class="badge-celeste badge-type text-capitalize"><?php echo e($record->status); ?></span>
                                </button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php elseif(strlen($search) >= 2): ?>
                        <p class="text-muted-celeste mt-2 mb-3" style="font-size:.8125rem">
                            No records match “<?php echo e($search); ?>”. Check the spelling or the student number.
                        </p>
                    <?php else: ?>
                        <p class="text-muted-celeste mt-2 mb-3" style="font-size:.8125rem">
                            Type at least two characters to search.
                        </p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2 mt-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="step-pill">2</span>
                        <label class="form-label mb-0">Choose the document</label>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($documentType): ?>
                        <button type="button" wire:click="clearType"
                                class="btn btn-sm btn-psu-outline" style="padding:.25rem .6rem">
                            <i class="bi bi-x-lg"></i> Clear
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="row g-2 mb-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $selected = $documentType === $value; ?>
                        <div class="col-sm-6">
                            <button type="button"
                                    class="doc-choice"
                                    wire:click="selectType('<?php echo e($value); ?>')"
                                    wire:key="type-<?php echo e($value); ?>"
                                    aria-pressed="<?php echo e($selected ? 'true' : 'false'); ?>">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selected): ?>
                                    <i class="bi bi-check-circle-fill doc-check"></i>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <span><?php echo e($label); ?></span>
                            </button>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['documentType'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback d-block mb-2"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <p class="text-muted-celeste mb-3" style="font-size:.75rem">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($documentType): ?>
                        Click <strong><?php echo e($types[$documentType]); ?></strong> again to unselect it, or pick a different document.
                    <?php else: ?>
                        Nothing is selected yet. Click a document to choose it.
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </p>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->eligibility): ?>
                    <div class="alert d-flex gap-2 py-2 px-3 mb-3" style="background:var(--psu-gold-soft);border:1px solid #f0dcae;color:#8a5c0c;font-size:.8125rem">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span><?php echo e($this->eligibility); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <div class="d-flex align-items-center gap-2 mb-2 mt-4">
                    <span class="step-pill">3</span>
                    <label class="form-label mb-0">Issuance</label>
                </div>

                <div class="row g-3">
                    <div class="col-sm-5">
                        <label for="issuedOn" class="form-label">Date of issue</label>
                        <input type="date" id="issuedOn" wire:model="issuedOn"
                               class="form-control <?php $__errorArgs = ['issuedOn'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['issuedOn'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="col-sm-7">
                        <label for="purpose" class="form-label">Purpose <span class="text-muted-celeste">(optional)</span></label>
                        <input type="text" id="purpose" wire:model="purpose" class="form-control"
                               placeholder="e.g. Employment requirement" maxlength="160">
                    </div>
                </div>

                <button wire:click="generate" class="btn btn-psu w-100 mt-4"
                        wire:loading.attr="disabled" wire:target="generate"
                        <?php if(! $this->ready): echo 'disabled'; endif; ?>>
                    <span wire:loading.remove wire:target="generate">
                        <i class="bi bi-shield-lock"></i>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $this->ready): ?>
                            Choose a student and a document
                        <?php else: ?>
                            Generate, hash, and stamp the QR
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </span>
                    <span wire:loading wire:target="generate">
                        <span class="spinner-border spinner-border-sm me-1"></span> Building the document…
                    </span>
                </button>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->issued): ?>
            <div class="card-celeste" wire:key="issued-<?php echo e($this->issued->id); ?>">
                <div class="card-header d-flex align-items-center gap-2" style="color:#157a34">
                    <i class="bi bi-check-circle-fill"></i> Document issued
                </div>
                <div class="p-3 p-md-4 text-center">
                    <img src="<?php echo e(route('certificates.qr', $this->issued)); ?>" alt="QR code for <?php echo e($this->issued->serial_number); ?>"
                         class="img-fluid mb-3" style="max-width:170px">

                    <div class="serial mb-1"><?php echo e($this->issued->serial_number); ?></div>
                    <div class="text-muted-celeste mb-3" style="font-size:.8125rem">
                        <?php echo e($this->issued->type_label); ?> · <?php echo e($this->issued->studentRecord?->full_name); ?>

                    </div>

                    <div class="text-start">
                        <div class="form-label">Fingerprint (SHA-256)</div>
                        <div class="hash-chip d-block mb-3"><?php echo e($this->issued->content_hash); ?></div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?php echo e(route('certificates.download', $this->issued)); ?>" class="btn btn-psu flex-fill btn-sm">
                            <i class="bi bi-download"></i> Download
                        </a>
                        <a href="<?php echo e(route('certificates.print', $this->issued)); ?>" target="_blank" class="btn btn-psu-outline flex-fill btn-sm">
                            <i class="bi bi-printer"></i> Print
                        </a>
                    </div>
                    <a href="<?php echo e(route('registrar.certificates.show', $this->issued)); ?>" class="btn btn-psu-outline btn-sm w-100 mt-2">
                        Open the record
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card-celeste">
                <div class="card-header">What happens when you generate</div>
                <div class="p-3 p-md-4">
                    <ol class="ps-3 mb-0" style="font-size:.875rem;line-height:1.9;color:var(--ink-muted)">
                        <li>A snapshot of every printed field is written to the record.</li>
                        <li>That snapshot is hashed with SHA-256 and a server-side key.</li>
                        <li>A QR code pointing at the public verification page is generated.</li>
                        <li>The PDF is rendered with the QR already embedded, then fingerprinted itself.</li>
                        <li>Only then does the document become downloadable or printable.</li>
                    </ol>
                    <p class="text-muted-celeste mt-3 mb-0" style="font-size:.8125rem">
                        Editing a record afterwards breaks its fingerprint, and verification will report the
                        document as altered. Use reissue instead — it supersedes the old copy and keeps the trail.
                    </p>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div><?php /**PATH C:\laragon\www\celeste\resources\views/livewire/certificates/generate-single.blade.php ENDPATH**/ ?>