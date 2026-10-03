<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['status' => null, 'label' => null, 'color' => null]));

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

foreach (array_filter((['status' => null, 'label' => null, 'color' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    if ($status !== null) {
        $label ??= method_exists($status, 'label') ? $status->label() : (string) $status;
        $color ??= method_exists($status, 'color') ? $status->color() : 'bg-base-200 text-base-content/70';
    }

    $color ??= 'bg-base-200 text-base-content/70';
?>

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo e($color); ?>">
    <?php echo e($label); ?>

</span>
<?php /**PATH /home/dev/projects/BookingKOM/resources/views/components/status-badge.blade.php ENDPATH**/ ?>