<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url(history: true)]
    public string $from = '';

    #[Url(history: true)]
    public string $to = '';

    public function mount(): void
    {
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->endOfMonth()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $from = Carbon::parse($this->from ?: 'first day of this month')->startOfDay();
        $to = Carbon::parse($this->to ?: 'today')->endOfDay();

        $base = Booking::query()->whereBetween('starts_at', [$from, $to]);

        $total = (clone $base)->count();
        $confirmed = (clone $base)->where('status', BookingStatus::Confirmed->value)->count();
        $completed = (clone $base)->where('status', BookingStatus::Completed->value)->count();
        $cancelled = (clone $base)->where('status', BookingStatus::Cancelled->value)->count();
        $rejected = (clone $base)->where('status', BookingStatus::Rejected->value)->count();
        $noShow = (clone $base)->where('status', BookingStatus::NoShow->value)->count();

        $byResource = Resource::query()
            ->with('type')
            ->withCount(['bookings as period_count' => fn ($query) => $query->whereBetween('bookings.starts_at', [$from, $to])])
            ->withCount(['bookings as fulfilled_count' => fn ($query) => $query->whereIn('bookings.status', [BookingStatus::Confirmed->value, BookingStatus::Completed->value])->whereBetween('bookings.starts_at', [$from, $to])])
            ->whereHas('bookings', fn ($query) => $query->whereBetween('bookings.starts_at', [$from, $to]))
            ->orderByDesc('period_count')
            ->take(12)
            ->get();

        $byDepartment = (clone $base)
            ->selectRaw('COALESCE(department, :unknown) as dept, count(*) as total', ['unknown' => __('Unassigned')])
            ->groupBy('dept')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        $topOrganisers = (clone $base)
            ->selectRaw('user_id, count(*) as total')
            ->with('organiser')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->take(8)
            ->get();

        $hours = Booking::query()
            ->whereBetween('starts_at', [$from, $to])
            ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Completed->value])
            ->get()
            ->sum(fn (Booking $booking): int => $booking->durationInMinutes());

        return [
            'rangeFrom' => $from,
            'rangeTo' => $to,
            'total' => $total,
            'confirmed' => $confirmed,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'rejected' => $rejected,
            'noShow' => $noShow,
            'bookedHours' => round($hours / 60, 1),
            'byResource' => $byResource,
            'byDepartment' => $byDepartment,
            'topOrganisers' => $topOrganisers,
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Reports')" :subtitle="__('Utilisation, demand and booking outcomes.')">
            <x-slot name="actions">
                <a href="{{ route('exports.bookings', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline btn-sm rounded-xl">{{ __('Export bookings') }}</a>
                <a href="{{ route('exports.utilisation', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline btn-sm rounded-xl">{{ __('Export utilisation') }}</a>
            </x-slot>
        </x-page-header>

        <div class="card bg-base-100 p-4">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <x-input-label for="from" :value="__('From')" />
                    <x-text-input wire:model.live="from" id="from" type="date" class="mt-1 block" />
                </div>
                <div>
                    <x-input-label for="to" :value="__('To')" />
                    <x-text-input wire:model.live="to" id="to" type="date" class="mt-1 block" />
                </div>
                <p class="text-xs text-base-content/55">
                    {{ $rangeFrom->format('d M Y') }} – {{ $rangeTo->format('d M Y') }}
                    <span wire:loading class="ms-1 text-primary">{{ __('Updating…') }}</span>
                </p>
            </div>
        </div>

        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card bg-base-100 p-5">
                <p class="text-sm text-base-content/60">{{ __('Total bookings') }}</p>
                <p class="mt-2 text-2xl font-bold text-base-content">{{ number_format($total) }}</p>
            </div>
            <div class="card bg-base-100 p-5">
                <p class="text-sm text-base-content/60">{{ __('Booked hours') }}</p>
                <p class="mt-2 text-2xl font-bold text-primary">{{ number_format($bookedHours, 1) }}</p>
            </div>
            <div class="card bg-base-100 p-5">
                <p class="text-sm text-base-content/60">{{ __('Completed') }}</p>
                <p class="mt-2 text-2xl font-bold text-success">{{ number_format($completed) }}</p>
            </div>
            <div class="card bg-base-100 p-5">
                <p class="text-sm text-base-content/60">{{ __('Cancelled') }}</p>
                <p class="mt-2 text-2xl font-bold text-error">{{ number_format($cancelled) }}</p>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="card bg-base-100 p-5 sm:p-6">
                <h2 class="text-base font-semibold text-base-content">{{ __('Most booked resources') }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="text-left text-xs uppercase tracking-wider text-base-content/60">{{ __('Resource') }}</th>
                                <th class="text-right text-xs uppercase tracking-wider text-base-content/60">{{ __('Bookings') }}</th>
                                <th class="text-right text-xs uppercase tracking-wider text-base-content/60">{{ __('Fulfilled') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byResource as $resource)
                                <tr>
                                    <td class="text-sm">
                                        <a href="{{ route('resources.show', $resource) }}" wire:navigate class="hover:text-primary">{{ $resource->name }}</a>
                                        <span class="block text-xs text-base-content/50">{{ $resource->type?->name }}</span>
                                    </td>
                                    <td class="text-right text-sm tabular-nums">{{ $resource->period_count }}</td>
                                    <td class="text-right text-sm tabular-nums text-base-content/60">{{ $resource->fulfilled_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-6 text-center text-sm text-base-content/60">{{ __('No bookings in this period.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-5">
                <div class="card bg-base-100 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-base-content">{{ __('Bookings by department') }}</h2>
                    <ul class="mt-4 space-y-3">
                        @forelse ($byDepartment as $row)
                            <li>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="text-base-content/80">{{ $row->dept }}</span>
                                    <span class="font-semibold tabular-nums">{{ $row->total }}</span>
                                </div>
                                <progress class="progress progress-primary h-1.5 w-full" value="{{ $row->total }}" max="{{ max(1, $byDepartment->max('total')) }}"></progress>
                            </li>
                        @empty
                            <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('No data.') }}</li>
                        @endforelse
                    </ul>
                </div>

                <div class="card bg-base-100 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-base-content">{{ __('Outcomes') }}</h2>
                    <ul class="mt-4 divide-y divide-base-200 text-sm">
                        <li class="flex items-center justify-between py-2.5"><span class="text-base-content/70">{{ __('Confirmed') }}</span><span class="font-semibold tabular-nums">{{ $confirmed }}</span></li>
                        <li class="flex items-center justify-between py-2.5"><span class="text-base-content/70">{{ __('Completed') }}</span><span class="font-semibold tabular-nums">{{ $completed }}</span></li>
                        <li class="flex items-center justify-between py-2.5"><span class="text-base-content/70">{{ __('Rejected') }}</span><span class="font-semibold tabular-nums">{{ $rejected }}</span></li>
                        <li class="flex items-center justify-between py-2.5"><span class="text-base-content/70">{{ __('No-shows') }}</span><span class="font-semibold tabular-nums text-error">{{ $noShow }}</span></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="card bg-base-100 p-5 sm:p-6">
            <h2 class="text-base font-semibold text-base-content">{{ __('Top organisers') }}</h2>
            <ul class="mt-4 divide-y divide-base-200">
                @forelse ($topOrganisers as $row)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span class="text-base-content/80">{{ $row->organiser?->name ?? __('Unknown') }}</span>
                        <span class="rounded-lg bg-base-200 px-2.5 py-1 text-xs font-semibold tabular-nums">{{ $row->total }}</span>
                    </li>
                @empty
                    <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('No data.') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
