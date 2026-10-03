<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'BookingKOM')); ?></title>

        <!-- Icons -->
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#4338ca">
        <script>
            (function () {
                try {
                    var t = localStorage.getItem('theme');
                    if (t === 'dark') t = 'bookingkom-dark';
                    if (t === 'light') t = 'bookingkom';
                    if (t) document.documentElement.setAttribute('data-theme', t);
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600;700&family=Fira+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        <meta name="asset-version" content="<?php echo e(\Illuminate\Support\Facades\Vite::asset('resources/css/app.css')); ?>">

        <!-- Scripts -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

        <script>
            (function () {
                function checkAssetVersion() {
                    var meta = document.querySelector('meta[name="asset-version"]');
                    if (!meta) return;
                    var current = meta.getAttribute('content');
                    var stored = null;
                    try { stored = localStorage.getItem('bookingkom-asset-version'); } catch (e) {}
                    if (stored && stored !== current) {
                        try { localStorage.setItem('bookingkom-asset-version', current); } catch (e) {}
                        window.location.reload();
                        return;
                    }
                    try { localStorage.setItem('bookingkom-asset-version', current); } catch (e) {}
                }
                document.addEventListener('DOMContentLoaded', checkAssetVersion);
                document.addEventListener('livewire:navigated', checkAssetVersion);
            })();
        </script>
    </head>
    <body class="min-h-screen bg-base-200 font-sans text-base-content antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <?php if (isset($component)) { $__componentOriginal6328f0deb07a8bef5ad2cd5691beb925 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6328f0deb07a8bef5ad2cd5691beb925 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.brand','data' => ['href' => url('/'),'class' => 'mb-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('brand'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(url('/')),'class' => 'mb-6']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6328f0deb07a8bef5ad2cd5691beb925)): ?>
<?php $attributes = $__attributesOriginal6328f0deb07a8bef5ad2cd5691beb925; ?>
<?php unset($__attributesOriginal6328f0deb07a8bef5ad2cd5691beb925); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6328f0deb07a8bef5ad2cd5691beb925)): ?>
<?php $component = $__componentOriginal6328f0deb07a8bef5ad2cd5691beb925; ?>
<?php unset($__componentOriginal6328f0deb07a8bef5ad2cd5691beb925); ?>
<?php endif; ?>

            <div class="w-full max-w-md rounded-2xl bg-base-100 p-6 shadow-xl ring-1 ring-base-300 sm:p-8">
                <?php echo e($slot); ?>

            </div>

            <p class="mt-6 text-xs text-base-content/50">
                <?php echo e(config('app.name', 'BookingKOM')); ?> · <?php echo e(now()->year); ?>

            </p>
        </div>
    </body>
</html>
<?php /**PATH /home/dev/projects/BookingKOM/resources/views/layouts/guest.blade.php ENDPATH**/ ?>