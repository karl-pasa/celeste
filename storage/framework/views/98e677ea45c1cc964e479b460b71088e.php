<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Verify a document'); ?> — CELESTE | <?php echo e(config('celeste.institution.short')); ?></title>
    <meta name="description" content="Check whether a document issued by <?php echo e(config('celeste.institution.name')); ?> is authentic. Scan its QR code or enter its serial number.">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?php echo e(asset('css/celeste.css')); ?>" rel="stylesheet">
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>
<body>
<div class="celeste-field">
    <nav class="container py-3 d-flex align-items-center justify-content-between">
        <a href="<?php echo e(route('home')); ?>" class="d-flex align-items-center gap-2 text-decoration-none">
            <span class="d-grid" style="width:34px;height:34px;border-radius:9px;background:rgba(255,255,255,.12);place-items:center;overflow:hidden">
                <img src="<?php echo e(asset('images/psu-seal-white.png')); ?>" alt=""
                    style="width:26px;height:26px;object-fit:contain"
                    onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
            </span>
            <span>
                <span class="d-block text-white fw-bold" style="letter-spacing:.04em;line-height:1.1">CELESTE</span>
                <span class="d-block" style="color:rgba(255,255,255,.6);font-size:.6875rem"><?php echo e(config('celeste.institution.short')); ?></span>
            </span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="<?php echo e(route('verify.scanner')); ?>" class="btn btn-sm" style="background:rgba(255,255,255,.12);color:#fff;border:0">
                <i class="bi bi-qr-code-scan"></i> <span class="d-none d-sm-inline">Scan</span>
            </a>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                <a href="<?php echo e(auth()->user()->isRegistrar() ? route('registrar.dashboard') : route('student.dashboard')); ?>"
                   class="btn btn-sm btn-light">Dashboard</a>
            <?php else: ?>
                <a href="<?php echo e(route('login')); ?>" class="btn btn-sm btn-light">Sign in</a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </nav>

    <main class="container pb-5">
        <?php echo $__env->yieldContent('content'); ?>
    </main>

    <footer class="container pb-4 text-center" style="color:rgba(255,255,255,.5);font-size:.75rem">
        &copy; <?php echo e(date('Y')); ?> <?php echo e(config('celeste.institution.name')); ?>, <?php echo e(config('celeste.institution.campus')); ?>.
        Office of the University Registrar.
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

<?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\laragon\www\celeste\resources\views/layouts/public.blade.php ENDPATH**/ ?>