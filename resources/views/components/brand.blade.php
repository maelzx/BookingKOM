@props(['href' => null])

<a href="{{ $href ?? route('dashboard') }}" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <span class="grid size-9 place-items-center rounded-xl bg-primary text-primary-content" aria-hidden="true">
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <rect x="3" y="5" width="18" height="16" rx="2.5" />
            <path stroke-linecap="round" d="M3 9.5h18M8 3v4M16 3v4" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.5 14.5 2.5 2.5 4.5-4.5" />
        </svg>
    </span>
    <span class="text-base font-bold tracking-tight text-base-content">Booking<span class="text-primary">KOM</span></span>
</a>
