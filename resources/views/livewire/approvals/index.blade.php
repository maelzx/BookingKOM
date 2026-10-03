<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public function approve(int $id, BookingService $service): void
    {
        $booking = Booking::findOrFail($id);
        Gate::authorize('approve', $booking);

        $service->approve($booking, auth()->user());

        session()->flash('status', __('Booking :reference approved.', ['reference' => $booking->reference]));
    }

    public function reject(int $id, BookingService $service): void
    {
        $booking = Booking::findOrFail($id);
        Gate::authorize('reject', $booking);

        $service->reject($booking, auth()->user());

        session()->flash('status', __('Booking :reference rejected.', ['reference' => $booking->reference]));
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $user = auth()->user();

        $bookings = Booking::query()
            ->with(['resources.type', 'organiser'])
            ->pending()
            ->when(! $user->isAdmin(), fn ($query) => $query->whereHas('resources', fn ($q) => $q->where('manager_id', $user->id)))
            ->orderBy('starts_at')
            ->paginate(15);

        return [
            'bookings' => $bookings,
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Approvals')" :subtitle="__('Bookings awaiting your decision.')" />

        @if (session('status'))
            <div role="alert" class="alert alert-success"><span>{{ session('status') }}</span></div>
        @endif

        @if ($bookings->isEmpty())
            <div class="card bg-base-100 p-10 text-center">
                <p class="text-sm text-base-content/60">{{ __('Nothing awaiting approval.') }}</p>
                <a href="{{ route('bookings.index') }}" wire:navigate class="btn btn-outline btn-sm mx-auto mt-4 rounded-xl">{{ __('View all bookings') }}</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($bookings as $booking)
                    <div class="card bg-base-100 p-5 sm:p-6" wire:key="approval-{{ $booking->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('bookings.show', $booking) }}" wire:navigate class="text-base font-semibold text-base-content hover:text-primary">{{ $booking->title }}</a>
                                    <x-status-badge :status="$booking->status" />
                                </div>
                                <p class="mt-1 font-mono text-xs text-base-content/50">{{ $booking->reference }}</p>
                                <p class="mt-2 text-sm text-base-content/70">
                                    {{ $booking->starts_at->format('l, d M Y H:i') }} – {{ $booking->ends_at->format('H:i') }}
                                </p>
                                <p class="mt-1 text-sm text-base-content/60">
                                    {{ __('Requested by :name', ['name' => $booking->organiser?->name ?? '—']) }}
                                    @if ($booking->department) · {{ $booking->department }} @endif
                                </p>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach ($booking->resources as $resource)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-base-200 px-2.5 py-1 text-xs text-base-content/80">
                                            {{ $resource->name }}
                                            @if ($resource->pivot->status === 'pending')
                                                <span class="size-1.5 rounded-full bg-warning"></span>
                                            @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                <button wire:click="approve({{ $booking->id }})" class="btn btn-success btn-sm rounded-xl">{{ __('Approve') }}</button>
                                <button wire:click="reject({{ $booking->id }})" wire:confirm="{{ __('Reject this booking?') }}" class="btn btn-error btn-outline btn-sm rounded-xl">{{ __('Reject') }}</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($bookings->hasPages())
                <div class="card bg-base-100 px-4 py-3">{{ $bookings->links() }}</div>
            @endif
        @endif
    </div>
</div>
