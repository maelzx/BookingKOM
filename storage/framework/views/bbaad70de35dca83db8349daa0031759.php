<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['active' => false]));

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

foreach (array_filter((['active' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$classes = ($active ?? false)
    ? 'block w-full rounded-lg px-4 py-2 text-start text-sm font-medium leading-5 bg-primary/10 text-primary'
    : 'block w-full rounded-lg px-4 py-2 text-start text-sm leading-5 text-base-content hover:bg-base-200 focus:outline-none transition';
?>

<a <?php echo e($attributes->merge(['class' => $classes])); ?> <?php if($active ?? false): ?> aria-current="page" <?php endif; ?>><?php echo e($slot); ?></a>
<?php /**PATH /home/dev/projects/BookingKOM/resources/views/components/dropdown-link.blade.php ENDPATH**/ ?>