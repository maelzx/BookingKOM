<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'BookingKOM')); ?></title>

        <link rel="icon" href="/favicon.ico" sizes="any">
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

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600;700&family=Fira+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        <meta name="asset-version" content="<?php echo e(\Illuminate\Support\Facades\Vite::asset('resources/css/app.css')); ?>">

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
    <body class="h-dvh overflow-hidden bg-base-200 font-sans text-base-content antialiased">
        <div class="flex h-dvh flex-col">
            <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('layout.navigation', []);

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-3660677015-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($header)): ?>
                <header class="border-b border-base-300/70 bg-base-100/80 backdrop-blur">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <?php echo e($header); ?>

                    </div>
                </header>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <main class="flex-1 overflow-y-scroll [scrollbar-gutter:stable]">
                <?php echo e($slot); ?>


                <footer class="mt-8 border-t border-base-300/70 bg-base-100/40">
                    <div class="mx-auto flex w-full max-w-7xl flex-col gap-1 px-4 py-5 text-xs text-base-content/50 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                        <span><?php echo e(config('app.name', 'BookingKOM')); ?></span>
                        <span><?php echo e(__('Resource booking & scheduling')); ?> · <?php echo e(now()->year); ?></span>
                    </div>
                </footer>
            </main>
        </div>
    </body>
</html>
<?php /**PATH /home/dev/projects/BookingKOM/resources/views/layouts/app.blade.php ENDPATH**/ ?>