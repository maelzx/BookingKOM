<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label', 'active' => false]));

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

foreach (array_filter((['label', 'active' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <button
        type="button"
        @click="open = ! open"
        :aria-expanded="open.toString()"
        aria-haspopup="true"
        class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium transition <?php echo e($active ? 'bg-primary text-primary-content' : 'text-base-content/70 hover:bg-base-200 hover:text-base-content'); ?>"
    >
        <?php echo e($label); ?>

        <svg class="h-3.5 w-3.5 opacity-60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute left-0 z-50 mt-2 w-52 rounded-box bg-base-100 p-1 shadow-lg ring-1 ring-base-300"
        style="display: none;"
        @keydown.escape.window="open = false"
        @click="open = false"
    >
        <?php echo e($slot); ?>

    </div>
</div>
<?php /**PATH /home/dev/projects/BookingKOM/resources/views/components/nav-group.blade.php ENDPATH**/ ?>