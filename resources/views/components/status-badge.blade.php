@props(['status' => null, 'label' => null, 'color' => null])

@php
    if ($status !== null) {
        $label ??= method_exists($status, 'label') ? $status->label() : (string) $status;
        $color ??= method_exists($status, 'color') ? $status->color() : 'bg-base-200 text-base-content/70';
    }

    $color ??= 'bg-base-200 text-base-content/70';
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $color }}">
    {{ $label }}
</span>
