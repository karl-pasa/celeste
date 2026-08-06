<div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->batch): ?>
        <div class="card-celeste mb-3" wire:key="batch-<?php echo e($this->batch->id); ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-check-circle-fill" style="color:var(--psu-green)"></i> <?php echo e($this->batch->reference); ?> finished</span>
                <a href="<?php echo e(route('registrar.certificates')); ?>?q=<?php echo e($this->batch->reference); ?>" class="btn btn-sm btn-psu-outline">
                    View the documents
                </a>
            </div>
            <div class="p-3 p-md-4">
                <div class="row g-3 text-center">
                    <div class="col-4">
                        <div class="stat-value" style="font-size:1.5rem"><?php echo e($this->batch->generated); ?></div>
                        <div class="stat-label">Generated</div>
                    </div>
                    <div class="col-4">
                        <div class="stat-value" style="font-size:1.5rem;color:<?php echo e($this->batch->failed ? 'var(--psu-red)' : 'var(--psu-navy-800)'); ?>">
                            <?php echo e($this->batch->failed); ?>

                        </div>
                        <div class="stat-label">Failed</div>
                    </div>
                    <div class="col-4">
                        <div class="stat-value" style="font-size:1.5rem"><?php echo e($this->batch->total); ?></div>
                        <div class="stat-label">Requested</div>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->batch->errors): ?>
                    <hr>
                    <h6 class="mb-2" style="font-size:.875rem">Records that did not generate</h6>
                    <ul class="mb-0 ps-3" style="font-size:.8125rem;color:var(--ink-muted)">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $this->batch->errors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error['student'] ?? $error['student_id']); ?> — <?php echo e($error['message']); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </ul>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card-celeste mb-3">
                <div class="card-header">Batch settings</div>
                <div class="p-3">
                    <label for="label" class="form-label">Batch name</label>
                    <input type="text" id="label" wire:model="label"
                           class="form-control mb-3 <?php $__errorArgs = ['label'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           placeholder="e.g. CAS graduates, Class of 2026">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['label'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback d-block mb-2"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <label for="batchType" class="form-label">Document to generate</label>
                    <select id="batchType" wire:model.live="documentType" class="form-select mb-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>

                    <div class="divider-label my-3">Narrow the list</div>

                    <label for="college" class="form-label">College</label>
                    <select id="college" wire:model.live="college" class="form-select mb-2">
                        <option value="">All colleges</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $colleges; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($option); ?>"><?php echo e($option); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>

                    <label for="program" class="form-label">Program</label>
                    <select id="program" wire:model.live="program" class="form-select mb-2">
                        <option value="">All programs</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($option); ?>"><?php echo e($option); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>

                    <label for="statusFilter" class="form-label">Status</label>
                    <select id="statusFilter" wire:model.live="status" class="form-select">
                        <option value="">Any status</option>
                        <option value="enrolled">Enrolled</option>
                        <option value="graduated">Graduated</option>
                        <option value="transferred">Transferred</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="card-celeste">
                <div class="card-header">Or upload a list</div>
                <div class="p-3">
                    <p class="text-muted-celeste mb-2" style="font-size:.8125rem">
                        A CSV with student numbers in the first column. A header row is fine.
                    </p>
                    <input type="file" wire:model="csv" class="form-control mb-2" accept=".csv,text/csv">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['csv'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback d-block mb-2"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <button wire:click="importCsv" class="btn btn-psu-outline btn-sm w-100" wire:loading.attr="disabled" wire:target="csv,importCsv">
                        <i class="bi bi-upload"></i> Match and select
                    </button>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('batch-import')): ?>
                        <p class="mb-0 mt-2" style="font-size:.8125rem;color:var(--psu-navy-600)"><?php echo e(session('batch-import')); ?></p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card-celeste">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>Student records</span>
                    <div class="d-flex align-items-center gap-2">
                        <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm"
                               placeholder="Search name or number" style="width:200px">
                        <span class="badge-celeste badge-type"><?php echo e(count($selected)); ?> selected</span>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['selected'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="alert alert-danger py-2 px-3 m-3 mb-0 small"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-celeste">
                        <thead>
                            <tr>
                                <th style="width:38px">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selectPage" aria-label="Select this page">
                                </th>
                                <th>Student</th>
                                <th>Number</th>
                                <th>Program</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr wire:key="student-<?php echo e($record->id); ?>">
                                    <td>
                                        <input type="checkbox" class="form-check-input" value="<?php echo e($record->id); ?>"
                                               wire:model.live="selected" aria-label="Select <?php echo e($record->full_name); ?>">
                                    </td>
                                    <td><?php echo e($record->full_name); ?></td>
                                    <td class="serial"><?php echo e($record->student_number); ?></td>
                                    <td class="text-muted-celeste"><?php echo e($record->program); ?></td>
                                    <td><span class="badge-celeste badge-type text-capitalize"><?php echo e($record->status); ?></span></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="5">
                                    <div class="empty">
                                        <div class="empty-icon"><i class="bi bi-funnel"></i></div>
                                        <h6>No records match these filters</h6>
                                        <p>Widen the college, program, or status filter to see more students.</p>
                                    </div>
                                </td></tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div><?php echo e($this->students->links()); ?></div>
                    <button wire:click="generate" class="btn btn-psu" wire:loading.attr="disabled" wire:target="generate"
                            <?php if(count($selected) === 0): echo 'disabled'; endif; ?>>
                        <span wire:loading.remove wire:target="generate">
                            <i class="bi bi-files"></i> Generate <?php echo e(count($selected) ?: ''); ?> document<?php echo e(count($selected) === 1 ? '' : 's'); ?>

                        </span>
                        <span wire:loading wire:target="generate">
                            <span class="spinner-border spinner-border-sm me-1"></span> Generating and hashing…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\celeste\resources\views/livewire/certificates/generate-batch.blade.php ENDPATH**/ ?>