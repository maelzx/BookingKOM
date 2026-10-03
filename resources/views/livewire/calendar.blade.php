<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\ResourceType;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url(history: true)]
    public string $view = 'month';

    #[Url(history: true)]
    public string $date = '';

    #[Url(history: true)]
    public ?int $resource = null;

    #[Url(history: true)]
    public ?int $type = null;

    #[Url(history: true)]
    public string $status = '';

    public function mount(): void
    {
        if ($this->date === '') {
            $this->date = now()->toDateString();
        }
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['day', 'week', 'month'], true) ? $view : 'month';
    }

    public function previous(): void
    {
        $anchor = $this->anchor();

        $this->date = match ($this->view) {
            'day' => $anchor->subDay()->toDateString(),
            'week' => $anchor->subWeek()->toDateString(),
            default => $anchor->subMonth()->toDateString(),
        };
    }

    public function next(): void
    {
        $anchor = $this->anchor();

        $this->date = match ($this->view) {
            'day' => $anchor->addDay()->toDateString(),
            'week' => $anchor->addWeek()->toDateString(),
            default => $anchor->addMonth()->toDateString(),
        };
    }

    public function today(): void
    {
        $this->date = now()->toDateString();
    }

    public function resetFilters(): void
    {
        $this->reset(['resource', 'type', 'status']);
    }

    protected function anchor(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date === '' ? 'today' : $this->date);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $anchor = $this->anchor();

        [$rangeStart, $rangeEnd] = match ($this->view) {
            'day' => [$anchor->startOfDay(), $anchor->endOfDay()],
            'week' => [$anchor->startOfWeek(CarbonImmutable::MONDAY)->startOfDay(), $anchor->endOfWeek(CarbonImmutable::SUNDAY)->endOfDay()],
            default => [$anchor->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY)->startOfDay(), $anchor->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY)->endOfDay()],
        };

        $bookings = Booking::query()
            ->with(['resources', 'organiser'])
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->resource, fn ($query) => $query->whereHas('resources', fn ($q) => $q->whereKey($this->resource)))
            ->when($this->type, fn ($query) => $query->whereHas('resources', fn ($q) => $q->where('resource_type_id', $this->type)))
            ->when($this->status === '', fn ($query) => $query->whereIn('status', [
                BookingStatus::Pending->value,
                BookingStatus::Approved->value,
                BookingStatus::Confirmed->value,
                BookingStatus::Completed->value,
                BookingStatus::NoShow->value,
            ]))
            ->orderBy('starts_at')
            ->get();

        $eventsByDate = $bookings->groupBy(fn (Booking $booking): string => $booking->starts_at->toDateString());

        $weeks = [];

        if ($this->view === 'month') {
            $cursor = $rangeStart;

            while ($cursor->lte($rangeEnd)) {
                $week = [];

                for ($i = 0; $i < 7; $i++) {
                    $week[] = $cursor->copy();
                    $cursor = $cursor->addDay();
                }

                $weeks[] = $week;
            }
        }

        return [
            'anchor' => $anchor,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'eventsByDate' => $eventsByDate,
            'weeks' => $weeks,
            'weekDays' => collect(range(0, 6))->map(fn (int $i): CarbonImmutable => $rangeStart->addDays($i)),
            'resources' => Resource::orderBy('name')->get(),
            'types' => ResourceType::orderBy('name')->get(),
            'statuses' => BookingStatus::cases(),
            'totalEvents' => $bookings->count(),
        ];
    }
}; ?>

<div class="py-7 sm:py-9">
    <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Calendar')" :subtitle="__('Resource availability across day, week and month views.')">
            <x-slot name="actions">
                @can('create', \App\Models\Booking::class)
                    <a href="{{ route('bookings.create', ['date' => $date]) }}" wire:navigate class="btn btn-primary btn-sm rounded-xl">{{ __('New booking') }}</a>
                @endcan
            </x-slot>
        </x-page-header>

        <div class="card bg-base-100 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="previous" class="btn btn-ghost btn-sm btn-square rounded-lg" aria-label="{{ __('Previous') }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" wire:click="today" class="btn btn-ghost btn-sm rounded-lg">{{ __('Today') }}</button>
                    <button type="button" wire:click="next" class="btn btn-ghost btn-sm btn-square rounded-lg" aria-label="{{ __('Next') }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <h2 class="ms-2 text-base font-semibold text-base-content sm:text-lg">
                        @if ($view === 'day')
                            {{ $anchor->format('D, d M Y') }}
                        @elseif ($view === 'week')
                            {{ $rangeStart->format('d M') }} – {{ $rangeEnd->format('d M Y') }}
                        @else
                            {{ $anchor->format('F Y') }}
                        @endif
                    </h2>
                </div>

                <div class="join">
                    @foreach (['day' => __('Day'), 'week' => __('Week'), 'month' => __('Month')] as $key => $label)
                        <button type="button" wire:click="setView('{{ $key }}')" class="btn btn-sm join-item {{ $view === $key ? 'btn-primary' : 'btn-ghost' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 border-t border-base-200 pt-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="sr-only" for="calendar-resource">{{ __('Resource') }}</label>
                <select id="calendar-resource" wire:model.live="resource" class="select select-bordered w-full border-base-300 focus:border-primary focus:ring-primary">
                    <option value="">{{ __('All resources') }}</option>
                    @foreach ($resources as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>

                <label class="sr-only" for="calendar-type">{{ __('Type') }}</label>
                <select id="calendar-type" wire:model.live="type" class="select select-bordered w-full border-base-300 focus:border-primary focus:ring-primary">
                    <option value="">{{ __('All types') }}</option>
                    @foreach ($types as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>

                <label class="sr-only" for="calendar-status">{{ __('Status') }}</label>
                <select id="calendar-status" wire:model.live="status" class="select select-bordered w-full border-base-300 focus:border-primary focus:ring-primary">
                    <option value="">{{ __('Active & completed') }}</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>

                <div class="flex items-center justify-between gap-2 sm:justify-end">
                    <span class="text-xs text-base-content/55" aria-live="polite">
                        {{ trans_choice(':count booking|:count bookings', $totalEvents, ['count' => $totalEvents]) }}
                        <span wire:loading class="ms-1 text-primary">{{ __('Updating…') }}</span>
                    </span>
                    @if ($resource || $type || $status !== '')
                        <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-xs rounded-lg">{{ __('Clear') }}</button>
                    @endif
                </div>
            </div>
        </div>

        @if ($view === 'month')
            <div class="card overflow-hidden bg-base-100">
                <div class="grid grid-cols-7 border-b border-base-300 bg-base-200 text-center text-xs font-semibold uppercase tracking-wide text-base-content/60">
                    @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                        <div class="px-2 py-2.5">{{ __($day) }}</div>
                    @endforeach
                </div>
                <div class="grid grid-cols-7">
                    @foreach ($weeks as $week)
                        @foreach ($week as $day)
                            @php
                                $dayEvents = $eventsByDate->get($day->toDateString(), collect());
                                $isToday = $day->isToday();
                                $inMonth = $day->month === $anchor->month;
                            @endphp
                            <div class="min-h-24 border-b border-e border-base-200 p-1.5 last:border-e-0 sm:min-h-28 {{ $inMonth ? 'bg-base-100' : 'bg-base-200/40' }}">
                                <div class="flex items-center justify-between px-0.5">
                                    <span class="grid size-6 place-items-center rounded-full text-xs font-semibold {{ $isToday ? 'bg-primary text-primary-content' : ($inMonth ? 'text-base-content/80' : 'text-base-content/40') }}">{{ $day->day }}</span>
                                    @if ($dayEvents->count() > 0)
                                        <span class="text-[10px] text-base-content/45">{{ $dayEvents->count() }}</span>
                                    @endif
                                </div>
                                <div class="mt-1 space-y-0.5">
                                    @foreach ($dayEvents->take(3) as $event)
                                        <a href="{{ route('bookings.show', $event) }}" wire:navigate class="calendar-event {{ $event->status->color() }}" title="{{ $event->starts_at->format('H:i') }} {{ $event->title }}">
                                            {{ $event->starts_at->format('H:i') }} {{ $event->title }}
                                        </a>
                                    @endforeach
                                    @if ($dayEvents->count() > 3)
                                        <a href="{{ route('calendar', ['view' => 'day', 'date' => $day->toDateString()]) }}" wire:navigate class="block px-1.5 text-[10px] font-medium text-primary">+{{ $dayEvents->count() - 3 }} {{ __('more') }}</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @elseif ($view === 'week')
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
                @foreach ($weekDays as $day)
                    @php $dayEvents = $eventsByDate->get($day->toDateString(), collect()); @endphp
                    <div class="card bg-base-100 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold {{ $day->isToday() ? 'text-primary' : 'text-base-content' }}">{{ $day->format('D d M') }}</span>
                            <span class="text-[11px] text-base-content/45">{{ $dayEvents->count() }}</span>
                        </div>
                        <div class="mt-2 space-y-1">
                            @forelse ($dayEvents as $event)
                                <a href="{{ route('bookings.show', $event) }}" wire:navigate class="calendar-event {{ $event->status->color() }}">
                                    {{ $event->starts_at->format('H:i') }} {{ $event->title }}
                                </a>
                            @empty
                                <p class="rounded-lg bg-base-200/60 px-2 py-3 text-center text-[11px] text-base-content/50">{{ __('Free') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card bg-base-100 p-4 sm:p-6">
                <ul class="divide-y divide-base-200">
                    @forelse (collect($eventsByDate->get($anchor->toDateString(), collect()))->sortBy('starts_at') as $event)
                        <li>
                            <a href="{{ route('bookings.show', $event) }}" wire:navigate class="flex flex-wrap items-center gap-4 rounded-lg py-3 transition hover:px-2 hover:bg-base-200/70">
                                <div class="w-28 shrink-0 text-sm font-semibold tabular-nums text-primary">{{ $event->starts_at->format('H:i') }} – {{ $event->ends_at->format('H:i') }}</div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-base-content">{{ $event->title }}</p>
                                    <p class="truncate text-xs text-base-content/55">{{ $event->resources->pluck('name')->join(', ') }} · {{ $event->organiser?->name }}</p>
                                </div>
                                <x-status-badge :status="$event->status" />
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl bg-base-200/60 px-4 py-12 text-center text-sm text-base-content/60">{{ __('No bookings on this day.') }}</li>
                    @endforelse
                </ul>
            </div>
        @endif
    </div>
</div>
