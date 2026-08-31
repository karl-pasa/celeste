<?php $__env->startSection('title', 'Sign in'); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-card">
    <div class="auth-head lockup">
        <img src="<?php echo e(asset('images/psu-seal.png')); ?>" alt="" class="seal" onerror="this.style.display='none'">
        <p class="university"><?php echo e(config('celeste.institution.name')); ?></p>
        <p class="campus"><?php echo e(config('celeste.institution.campus')); ?></p>
        <h1 class="system-name">CELESTE</h1>
        <p class="system-full">Certificate Authentication and Verification System</p>
        <p class="system-note">
            Issued documents carry a QR code and a cryptographic hash.
            Anyone can check a document without an account.
        </p>
    </div>

    <div class="auth-body">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
            <div class="alert alert-success py-2 px-3 small mb-3"><?php echo e(session('status')); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <form method="POST" action="<?php echo e(route('login.attempt')); ?>" x-data="{ role: '<?php echo e(old('role', $role)); ?>' }">
            <?php echo csrf_field(); ?>

            <div class="role-tabs mb-3" role="tablist" aria-label="Account type">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
                    'student'   => ['Student', 'bi-mortarboard'],
                    'registrar' => ['Registrar', 'bi-shield-lock'],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => [$label, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <button type="button" role="tab"
                            class="role-tab" :class="{ 'active': role === '<?php echo e($value); ?>' }"
                            :aria-selected="role === '<?php echo e($value); ?>'"
                            @click="role = '<?php echo e($value); ?>'">
                        <i class="bi <?php echo e($icon); ?>"></i><?php echo e($label); ?>

                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <input type="hidden" name="role" :value="role">

            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope"></i> Institutional email
                </label>
                <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>"
                       class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       :placeholder="role === 'student'
                            ? 'jdelacruz922.pbox{{ config('celeste.institution.email_domain', 'parsu.edu.ph') }}'
                            : 'registrar{{ config('celeste.institution.email_domain', 'parsu.edu.ph') }}'"
                       autocomplete="username" autofocus required>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            
            <div class="mb-3" x-data="{ show: false }">
                <label for="password" class="form-label">
                    <i class="bi bi-lock"></i> Password
                </label>
                <div class="input-group">
                    <input :type="show ? 'text' : 'password'" id="password" name="password"
                           class="form-control border-end-0 <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           placeholder="Enter your password"
                           autocomplete="current-password" required>
                    <button class="input-group-text" type="button" @click="show = !show"
                            :aria-label="show ? 'Hide password' : 'Show password'">
                        <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                    </button>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(config('celeste.auth.allow_remember', false)): ?>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small text-muted-celeste" for="remember">Keep me signed in</label>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <button type="submit" class="btn btn-psu w-100">
                <i class="bi bi-box-arrow-in-right"></i> Sign in
            </button>
        </form>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Route::has('password.request')): ?>
            <div class="text-center mt-3">
                <a href="<?php echo e(route('password.request')); ?>" class="text-muted-celeste" style="font-size:.8125rem">
                    Forgot your password?
                </a>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="divider-label my-3">or</div>

        <a href="<?php echo e(route('verify')); ?>" class="btn btn-psu-outline w-100">
            <i class="bi bi-patch-check"></i> Verify a document without signing in
        </a>
    </div>

    <div class="auth-foot">
        &copy; <?php echo e(date('Y')); ?> <?php echo e(config('celeste.institution.name')); ?>. All rights reserved.
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/auth/login.blade.php ENDPATH**/ ?>