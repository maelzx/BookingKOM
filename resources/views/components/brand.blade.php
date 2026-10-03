@props(['href' => null])

<a href="{{ $href ?? route('dashboard') }}" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <img src="{{ asset('images/brand/lockup-light.png') }}" alt="BookingKOM" class="h-8 w-auto dark:hidden">
    <img src="{{ asset('images/brand/lockup-dark.png') }}" alt="BookingKOM" class="hidden h-8 w-auto dark:block">
</a>
