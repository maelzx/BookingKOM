<?php

use App\Enums\BookingStatus;
use App\Models\Resource;
use App\Services\BookingAvailability;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public Resource $resource;

    public function mount(Resource $resource): void
    {
        $this->resource = $resource->load('type');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $availability = app(BookingAvailability::class);

        return [
            'slots' => $availability->slotsForDate($this->resource, now()),
            'upcoming' => $this->resource->bookings()
                ->where('bookings.starts_at', '>=', now())
                ->whereIn('bookings.status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])
                ->orderBy('bookings.starts_at')
                ->take(6)
                ->get(['bookings.id', 'bookings.starts_at', 'bookings.ends_at', 'bookings.status']),
        ];
    }
}; ?>

<div class="w-full max-w-md space-y-4">
    <div class="card bg-base-100 p-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-base-content">{{ $resource->name }}</h1>
                <p class="font-mono text-xs text-base-content/50">{{ $resource->code }}</p>
            </div>
            <x-status-badge :status="$resource->status" />
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-base-200 pt-4 text-sm">
            <div>
                <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Type') }}</dt>
                <dd class="mt-0.5 text-base-content">{{ $resource->type?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Location') }}</dt>
                <dd class="mt-0.5 text-base-content">{{ $resource->location ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Capacity') }}</dt>
                <dd class="mt-0.5 text-base-content">{{ $resource->capacity ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Approval') }}</dt>
                <dd class="mt-0.5 text-base-content">{{ $resource->approval_mode->label() }}</dd>
            </div>
        </dl>

        @if ($resource->description)
            <p class="mt-4 border-t border-base-200 pt-4 text-sm text-base-content/70">{{ $resource->description }}</p>
        @endif
    </div>

    <div class="card bg-base-100 p-6">
        <h2 class="text-base font-semibold text-base-content">{{ __('Available today') }}</h2>
        <div class="mt-3 flex flex-wrap gap-1.5">
            @forelse ($slots as $slot)
                <span class="rounded-md px-2 py-1 text-xs font-medium tabular-nums {{ $slot['available'] ? 'bg-success/10 text-success' : 'bg-error/10 text-error line-through' }}">
                    {{ $slot['start']->format('H:i') }}
                </span>
            @empty
                <p class="text-sm text-base-content/60">{{ __('Not available today.') }}</p>
            @endforelse
        </div>
    </div>

    <div class="card bg-base-100 p-6">
        <h2 class="text-base font-semibold text-base-content">{{ __('Upcoming reservations') }}</h2>
        <ul class="mt-3 divide-y divide-base-200 text-sm">
            @forelse ($upcoming as $booking)
                <li class="flex items-center justify-between gap-3 py-2.5">
                    <span class="tabular-nums text-base-content/80">{{ $booking->starts_at->format('D d M H:i') }}</span>
                    <span class="text-xs text-base-content/50">{{ $booking->starts_at->format('H:i') }}–{{ $booking->ends_at->format('H:i') }}</span>
                </li>
            @empty
                <li class="py-3 text-center text-sm text-base-content/60">{{ __('No upcoming reservations.') }}</li>
            @endforelse
        </ul>
    </div>

    @if ($resource->isBookable())
        @auth
            <a href="{{ route('bookings.create', ['resource' => $resource->id]) }}" class="btn btn-primary w-full rounded-xl">{{ __('Book this resource') }}</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary w-full rounded-xl">{{ __('Sign in to book') }}</a>
        @endauth
    @else
        <div class="card bg-base-100 p-4 text-center text-sm text-base-content/60">{{ __('This resource is not currently bookable.') }}</div>
    @endif
</div>
