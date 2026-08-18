<?php $__env->startSection('title', 'Reset your password'); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-card">
    <div class="auth-head lockup">
        <img src="<?php echo e(asset('images/psu-seal.png')); ?>" alt="" class="seal" onerror="this.style.display='none'">
        <p class="university"><?php echo e(config('celeste.institution.name')); ?></p>
        <h1 class="system-name">CELESTE</h1>
        <p class="system-full">Reset your password</p>
    </div>

    <div class="auth-body">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
            <div class="alert alert-success py-2 px-3 small mb-3"><?php echo e(session('status')); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <p class="text-muted-celeste mb-3" style="font-size:.8125rem">
            Enter the email address on your account and we will send a reset link.
            The link expires in 15 minutes.
        </p>

        <form method="POST" action="<?php echo e(route('password.email')); ?>">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label for="email" class="form-label"><i class="bi bi-envelope"></i> Email address</label>
                <input type="email" id="email" name="email" value="<?php echo e(old('email')); ?>"
                       class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       placeholder="yourname{{ config('celeste.student_email_domain') }}" required autofocus>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback d-block"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <button type="submit" class="btn btn-psu w-100">
                <i class="bi bi-send"></i> Send reset link
            </button>
        </form>

        <div class="divider-label my-3">or</div>
        <a href="<?php echo e(route('login')); ?>" class="btn btn-psu-outline w-100">Back to sign in</a>
    </div>

    <div class="auth-foot">&copy; <?php echo e(date('Y')); ?> <?php echo e(config('celeste.institution.name')); ?>.</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.guest', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\celeste\resources\views/auth/forgot-password.blade.php ENDPATH**/ ?>