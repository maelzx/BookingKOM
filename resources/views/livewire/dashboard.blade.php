<?php

use App\Enums\BookingStatus;
use App\Enums\ResourceStatus;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $user = auth()->user();
        $canSeeAll = $user->hasRole(\App\Enums\Role::Admin, \App\Enums\Role::ResourceManager);

        $scoped = fn () => Booking::query()
            ->when(! $canSeeAll, fn ($query) => $query->where(function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhereHas('attendees', fn ($q) => $q->where('user_id', $user->id));
            }));

        $today = $scoped()
            ->with(['resources', 'organiser'])
            ->whereDate('starts_at', today())
            ->whereNotIn('status', [BookingStatus::Cancelled->value, BookingStatus::Rejected->value])
            ->orderBy('starts_at')
            ->get();

        $upcoming = $scoped()
            ->with(['resources', 'organiser'])
            ->whereBetween('starts_at', [now(), now()->addDays(7)])
            ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])
            ->whereDate('starts_at', '>', today())
            ->orderBy('starts_at')
            ->take(8)
            ->get();

        $pendingApprovals = $canSeeAll
            ? Booking::query()
                ->with(['resources', 'organiser'])
                ->pending()
                ->whereHas('resources', function ($query) use ($user): void {
                    $query->when(! $user->isAdmin(), fn ($q) => $q->where('manager_id', $user->id));
                })
                ->orderBy('starts_at')
                ->take(6)
                ->get()
            : collect();

        $pendingCount = $canSeeAll
            ? Booking::query()
                ->pending()
                ->whereHas('resources', function ($query) use ($user): void {
                    $query->when(! $user->isAdmin(), fn ($q) => $q->where('manager_id', $user->id));
                })
                ->count()
            : 0;

        $resourceCounts = Resource::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $topResources = Resource::query()
            ->withCount(['bookings as bookings_this_month' => fn ($query) => $query->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])])
            ->orderByDesc('bookings_this_month')
            ->take(5)
            ->get();

        return [
            'todayBookings' => $today,
            'upcomingBookings' => $upcoming,
            'pendingApprovals' => $pendingApprovals,
            'pendingCount' => $pendingCount,
            'totalResources' => Resource::count(),
            'activeResources' => (int) ($resourceCounts[ResourceStatus::Active->value] ?? 0),
            'maintenanceResources' => (int) ($resourceCounts[ResourceStatus::Maintenance->value] ?? 0),
            'todayCount' => $today->count(),
            'weekCount' => $scoped()->whereBetween('starts_at', [now(), now()->addDays(7)])->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])->count(),
            'topResources' => $topResources,
            'canSeeAll' => $canSeeAll,
        ];
    }
}; ?>

<div class="py-7 sm:py-9">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Dashboard')" :subtitle="$canSeeAll ? __('Today\'s operations across all resources.') : __('Your bookings at a glance.')">
            <x-slot name="actions">
                <a href="{{ route('calendar') }}" wire:navigate class="btn btn-outline btn-sm rounded-xl">{{ __('Calendar') }}</a>
                @can('create', \App\Models\Booking::class)
                    <a href="{{ route('bookings.create') }}" wire:navigate class="btn btn-primary btn-sm rounded-xl">{{ __('New booking') }}</a>
                @endcan
            </x-slot>
        </x-page-header>

        <section aria-label="{{ __('Summary') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('bookings.index') }}" wire:navigate class="card group bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-base-content/60">{{ __("Today's bookings") }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-base-content">{{ number_format($todayCount) }}</p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-primary/10 text-primary" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path stroke-linecap="round" d="M3 9.5h18M8 3v4M16 3v4"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-xs text-base-content/50">{{ __('Scheduled for today') }} <span class="ms-1 text-primary">→</span></p>
            </a>

            <a href="{{ route('bookings.index') }}" wire:navigate class="card bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-info/30 hover:shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-base-content/60">{{ __('Next 7 days') }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-info">{{ number_format($weekCount) }}</p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-info/10 text-info" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-xs text-base-content/50">{{ __('Upcoming active bookings') }} <span class="ms-1 text-info">→</span></p>
            </a>

            @can('approve-bookings')
                <a href="{{ route('approvals.index') }}" wire:navigate class="card bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-warning/30 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-base-content/60">{{ __('Pending approvals') }}</p>
                            <p class="mt-3 text-3xl font-bold tracking-tight {{ $pendingCount > 0 ? 'text-warning' : 'text-base-content' }}">{{ number_format($pendingCount) }}</p>
                        </div>
                        <span class="grid size-11 place-items-center rounded-2xl bg-warning/10 text-warning" aria-hidden="true">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.5 11 14.5 15.5 10M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3Z"/></svg>
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-base-content/50">{{ __('Awaiting your decision') }} <span class="ms-1 text-warning">→</span></p>
                </a>
            @endcan

            <a href="{{ route('resources.index') }}" wire:navigate class="card bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-success/30 hover:shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-base-content/60">{{ __('Active resources') }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-success">{{ number_format($activeResources) }}</p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-success/10 text-success" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 7.8 7.5 4.4 7.5-4.4M12 12.2V21"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-xs text-base-content/50">{{ __('of :total total', ['total' => $totalResources]) }} <span class="ms-1 text-success">→</span></p>
            </a>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="card bg-base-100 p-5 sm:p-6 lg:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-base-content">{{ __("Today's schedule") }}</h2>
                        <p class="mt-1 text-sm text-base-content/55">{{ now()->format('l, d M Y') }}</p>
                    </div>
                    <a href="{{ route('calendar') }}" wire:navigate class="link link-hover text-xs font-medium text-primary">{{ __('Open calendar') }}</a>
                </div>
                <ul class="mt-4 divide-y divide-base-200">
                    @forelse ($todayBookings as $booking)
                        <li>
                            <a href="{{ route('bookings.show', $booking) }}" wire:navigate class="flex items-start gap-4 rounded-lg py-3 transition hover:px-2 hover:bg-base-200/70">
                                <div class="w-16 shrink-0 text-sm font-semibold tabular-nums text-primary">{{ $booking->starts_at->format('H:i') }}</div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-base-content">{{ $booking->title }}</p>
                                    <p class="truncate text-xs text-base-content/55">{{ $booking->resources->pluck('name')->join(', ') }}</p>
                                </div>
                                <x-status-badge :status="$booking->status" />
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl bg-base-200/60 px-4 py-8 text-center text-sm text-base-content/60">{{ __('Nothing scheduled today.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card bg-base-100 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-base-content">{{ __('Most booked this month') }}</h2>
                </div>
                <ul class="mt-4 space-y-4">
                    @forelse ($topResources as $resource)
                        <li>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <a href="{{ route('resources.show', $resource) }}" wire:navigate class="min-w-0 truncate text-base-content/80 hover:text-primary">{{ $resource->name }}</a>
                                <span class="font-semibold tabular-nums text-base-content">{{ number_format($resource->bookings_this_month) }}</span>
                            </div>
                            <progress class="progress progress-primary h-1.5 w-full" value="{{ $resource->bookings_this_month }}" max="{{ max(1, $topResources->max('bookings_this_month')) }}"></progress>
                        </li>
                    @empty
                        <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('No bookings yet.') }}</li>
                    @endforelse
                </ul>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="card bg-base-100 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-base-content">{{ __('Upcoming') }}</h2>
                    <a href="{{ route('bookings.index') }}" wire:navigate class="link link-hover text-xs font-medium text-primary">{{ __('View all') }}</a>
                </div>
                <ul class="mt-4 divide-y divide-base-200">
                    @forelse ($upcomingBookings as $booking)
                        <li>
                            <a href="{{ route('bookings.show', $booking) }}" wire:navigate class="flex items-center justify-between gap-3 rounded-lg py-3 transition hover:px-2 hover:bg-base-200/70">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-base-content">{{ $booking->title }}</p>
                                    <p class="truncate text-xs text-base-content/55">{{ $booking->resources->pluck('name')->join(', ') }}</p>
                                </div>
                                <span class="shrink-0 text-xs tabular-nums text-base-content/60">{{ $booking->starts_at->format('d M H:i') }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('No upcoming bookings.') }}</li>
                    @endforelse
                </ul>
            </div>

            @can('approve-bookings')
                <div class="card bg-base-100 p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-base-content">{{ __('Awaiting your approval') }}</h2>
                        <a href="{{ route('approvals.index') }}" wire:navigate class="link link-hover text-xs font-medium text-primary">{{ __('Review') }}</a>
                    </div>
                    <ul class="mt-4 divide-y divide-base-200">
                        @forelse ($pendingApprovals as $booking)
                            <li>
                                <a href="{{ route('bookings.show', $booking) }}" wire:navigate class="flex items-center justify-between gap-3 rounded-lg py-3 transition hover:px-2 hover:bg-base-200/70">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-base-content">{{ $booking->title }}</p>
                                        <p class="truncate text-xs text-base-content/55">{{ $booking->organiser?->name }} · {{ $booking->resources->pluck('name')->join(', ') }}</p>
                                    </div>
                                    <span class="shrink-0 text-xs tabular-nums text-base-content/60">{{ $booking->starts_at->format('d M H:i') }}</span>
                                </a>
                            </li>
                        @empty
                            <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('Nothing awaiting approval.') }}</li>
                        @endforelse
                    </ul>
                </div>
            @endcan
        </section>
    </div>
</div>
