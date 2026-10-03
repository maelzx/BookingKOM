<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['href' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['href' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<a href="<?php echo e($href ?? route('dashboard')); ?>" wire:navigate <?php echo e($attributes->merge(['class' => 'flex items-center gap-2'])); ?>>
    <span class="grid size-9 place-items-center rounded-xl bg-primary text-primary-content" aria-hidden="true">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <rect x="3" y="5" width="18" height="16" rx="2.5" />
            <path stroke-linecap="round" d="M3 9.5h18M8 3v4M16 3v4" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.5 14.5 2.5 2.5 4.5-4.5" />
        </svg>
    </span>
    <span class="text-base font-bold tracking-tight text-base-content">Booking<span class="text-primary">KOM</span></span>
</a>
<?php /**PATH /home/dev/projects/BookingKOM/resources/views/components/brand.blade.php ENDPATH**/ ?>