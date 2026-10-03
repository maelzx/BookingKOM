<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Resource;
use App\Services\BookingService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $status = '';

    #[Url(history: true)]
    public ?int $resource = null;

    #[Url(history: true)]
    public string $scope = '';

    public function mount(): void
    {
        if ($this->scope === '') {
            $this->scope = $this->defaultScope();
        }
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'resource']);
        $this->scope = $this->defaultScope();
        $this->resetPage();
    }

    /**
     * Regular users start on their own bookings; managers and admins on the
     * upcoming operational view. Everyone can still switch to see all.
     */
    protected function defaultScope(): string
    {
        return auth()->user()->hasRole(\App\Enums\Role::Admin, \App\Enums\Role::ResourceManager)
            ? 'upcoming'
            : 'mine';
    }

    public function cancel(int $id, BookingService $service): void
    {
        $booking = Booking::findOrFail($id);

        Gate::authorize('cancel', $booking);

        $service->cancel($booking, auth()->user(), __('Cancelled by :name', ['name' => auth()->user()->name]));

        session()->flash('status', __('Booking cancelled.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $user = auth()->user();

        $bookings = Booking::query()
            ->with(['resources', 'organiser'])
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('title', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhere('purpose', 'like', $term)
                        ->orWhereHas('organiser', fn ($q) => $q->where('name', 'like', $term));
                });
            })
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->resource, fn ($query) => $query->whereHas('resources', fn ($q) => $q->whereKey($this->resource)))
            ->when($this->scope === 'upcoming', fn ($query) => $query->where('ends_at', '>=', now())->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Approved->value, BookingStatus::Confirmed->value]))
            ->when($this->scope === 'past', fn ($query) => $query->where('ends_at', '<', now()))
            ->when($this->scope === 'mine', fn ($query) => $query->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhereHas('attendees', fn ($q) => $q->where('user_id', $user->id))
                    ->orWhereHas('resources', fn ($q) => $q->where('manager_id', $user->id));
            }))
            ->orderByDesc('starts_at')
            ->paginate(15);

        return [
            'bookings' => $bookings,
            'resources' => Resource::orderBy('name')->get(),
            'statuses' => BookingStatus::cases(),
            'defaultScope' => $this->defaultScope(),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Bookings')" :subtitle="__('Create, track and manage resource bookings.')">
            <x-slot name="actions">
                <a href="{{ route('calendar') }}" wire:navigate class="btn btn-outline btn-sm rounded-xl">{{ __('Calendar') }}</a>
                @can('create', \App\Models\Booking::class)
                    <a href="{{ route('bookings.create') }}" wire:navigate class="btn btn-primary btn-sm rounded-xl">{{ __('New booking') }}</a>
                @endcan
            </x-slot>
        </x-page-header>

        @if (session('status'))
            <div role="alert" class="alert alert-success">
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <div class="card bg-base-100 p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <label for="booking-search" class="sr-only">{{ __('Search bookings') }}</label>
                    <x-text-input wire:model.live.debounce.300ms="search" id="booking-search" type="search" class="block w-full" placeholder="{{ __('Search title, reference, organiser…') }}" />
                </div>

                <label for="booking-scope" class="sr-only">{{ __('Scope') }}</label>
                <select wire:model.live="scope" id="booking-scope" class="select select-bordered w-full border-base-300 focus:border-primary focus:ring-primary">
                    <option value="mine">{{ __('My bookings') }}</option>
                    <option value="upcoming">{{ __('Upcoming') }}</option>
                    <option value="all">{{ __('All') }}</option>
                    <option value="past">{{ __('Past') }}</option>
                </select>

                <label for="booking-status-filter" class="sr-only">{{ __('Status') }}</label>
                <select wire:model.live="status" id="booking-status-filter" class="select select-bordered w-full border-base-300 focus:border-primary focus:ring-primary">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>

                <label for="booking-resource-filter" class="sr-only">{{ __('Resource') }}</label>
                <select wire:model.live="resource" id="booking-resource-filter" class="select select-bordered w-full border-base-300 focus:border-primary focus:ring-primary">
                    <option value="">{{ __('All resources') }}</option>
                    @foreach ($resources as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-base-200 pt-3">
                <p class="text-xs text-base-content/55" aria-live="polite">
                    {{ __('Showing :from–:to of :total bookings', ['from' => $bookings->firstItem() ?? 0, 'to' => $bookings->lastItem() ?? 0, 'total' => $bookings->total()]) }}
                    <span wire:loading class="ms-1 text-primary">{{ __('Updating…') }}</span>
                </p>

                @if ($search !== '' || $status !== '' || $resource || $scope !== $defaultScope)
                    <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-xs rounded-lg text-base-content/70">{{ __('Clear filters') }}</button>
                @endif
            </div>
        </div>

        <x-data-table :paginator="$bookings">
            <x-slot name="table">
                <table class="table table-sm">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Reference') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Title') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('When') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Resources') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300">
                        @forelse ($bookings as $booking)
                            <tr wire:key="booking-{{ $booking->id }}" class="hover:bg-base-200">
                                <td class="px-4 py-3 font-mono text-xs text-base-content/70">{{ $booking->reference }}</td>
                                <td class="px-4 py-3 text-sm text-base-content">
                                    <a href="{{ route('bookings.show', $booking) }}" wire:navigate class="font-medium hover:text-primary">{{ $booking->title }}</a>
                                    <p class="text-xs text-base-content/50">{{ $booking->organiser?->name }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-base-content/70">
                                    {{ $booking->starts_at->format('d M Y') }}
                                    <span class="block text-xs tabular-nums">{{ $booking->starts_at->format('H:i') }}–{{ $booking->ends_at->format('H:i') }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $booking->resources->pluck('name')->join(', ') ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm"><x-status-badge :status="$booking->status" /></td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <a href="{{ route('bookings.show', $booking) }}" wire:navigate class="text-primary hover:opacity-80">{{ __('View') }}</a>
                                    @can('update', $booking)
                                        <a href="{{ route('bookings.edit', $booking) }}" wire:navigate class="ms-3 text-base-content/70 hover:text-base-content">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('cancel', $booking)
                                        <button wire:click="cancel({{ $booking->id }})" wire:confirm="{{ __('Cancel this booking?') }}" class="ms-3 text-error hover:opacity-80">{{ __('Cancel') }}</button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-base-content/60">{{ __('No bookings found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-slot>

            <x-slot name="cards">
                <ul class="divide-y divide-base-300">
                    @forelse ($bookings as $booking)
                        <li class="space-y-2 p-4" wire:key="booking-card-{{ $booking->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('bookings.show', $booking) }}" wire:navigate class="font-medium text-base-content hover:text-primary">{{ $booking->title }}</a>
                                    <p class="font-mono text-xs text-base-content/50">{{ $booking->reference }}</p>
                                </div>
                                <x-status-badge :status="$booking->status" />
                            </div>

                            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-base-content/60">
                                <div><dt class="inline text-base-content/40">{{ __('When') }}:</dt> {{ $booking->starts_at->format('d M H:i') }}</div>
                                <div><dt class="inline text-base-content/40">{{ __('By') }}:</dt> {{ $booking->organiser?->name }}</div>
                                <div class="col-span-2"><dt class="inline text-base-content/40">{{ __('Resources') }}:</dt> {{ $booking->resources->pluck('name')->join(', ') ?: '—' }}</div>
                            </dl>

                            <div class="flex items-center gap-4 pt-1 text-sm">
                                <a href="{{ route('bookings.show', $booking) }}" wire:navigate class="text-primary hover:opacity-80">{{ __('View') }}</a>
                                @can('update', $booking)
                                    <a href="{{ route('bookings.edit', $booking) }}" wire:navigate class="text-base-content/70 hover:text-base-content">{{ __('Edit') }}</a>
                                @endcan
                                @can('cancel', $booking)
                                    <button wire:click="cancel({{ $booking->id }})" wire:confirm="{{ __('Cancel this booking?') }}" class="text-error hover:opacity-80">{{ __('Cancel') }}</button>
                                @endcan
                            </div>
                        </li>
                    @empty
                        <li class="p-8 text-center text-sm text-base-content/60">{{ __('No bookings found.') }}</li>
                    @endforelse
                </ul>
            </x-slot>
        </x-data-table>
    </div>
</div>
