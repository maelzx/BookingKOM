<?php

use App\Enums\BookingStatus;
use App\Exceptions\BookingConflictException;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\User;
use App\Services\BookingAvailability;
use App\Services\BookingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?Booking $booking = null;

    public string $title = '';

    public string $purpose = '';

    public string $department = '';

    public string $date = '';

    public string $startTime = '09:00';

    public string $endTime = '10:00';

    /**
     * @var array<int, int>
     */
    public array $resourceIds = [];

    /**
     * @var array<int, int>
     */
    public array $attendeeIds = [];

    public string $frequency = 'none';

    public int $interval = 1;

    public int $occurrences = 4;

    public ?string $until = null;

    /**
     * @var array<int, int>
     */
    public array $weeklyDays = [];

    public function mount(?Booking $booking = null): void
    {
        $this->booking = $booking;

        if ($booking?->exists) {
            Gate::authorize('update', $booking);

            $this->title = $booking->title;
            $this->purpose = (string) $booking->purpose;
            $this->department = (string) $booking->department;
            $this->date = $booking->starts_at->toDateString();
            $this->startTime = $booking->starts_at->format('H:i');
            $this->endTime = $booking->ends_at->format('H:i');
            $this->resourceIds = $booking->resources->pluck('id')->all();
            $this->attendeeIds = $booking->attendees->where('is_organiser', false)->pluck('user_id')->filter()->all();

            return;
        }

        Gate::authorize('create', Booking::class);

        $this->date = request()->string('date')->toString() ?: now()->toDateString();

        if ($resourceId = request()->integer('resource')) {
            $this->resourceIds = [$resourceId];
        }

        $defaultMinutes = (int) \App\Models\Setting::get('default_booking_minutes', 60);
        $this->startTime = '09:00';
        $this->endTime = Carbon::parse('09:00')->addMinutes($defaultMinutes)->format('H:i');
    }

    public function save(BookingService $service, BookingAvailability $availability): void
    {
        $this->booking?->exists
            ? Gate::authorize('update', $this->booking)
            : Gate::authorize('create', Booking::class);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:5000'],
            'department' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'endTime' => ['required', 'date_format:H:i'],
            'resourceIds' => ['required', 'array', 'min:1'],
            'resourceIds.*' => ['integer', 'exists:resources,id'],
            'attendeeIds' => ['array'],
            'attendeeIds.*' => ['integer', 'exists:users,id'],
            'frequency' => ['required', 'in:none,daily,weekly,monthly'],
            'interval' => ['required', 'integer', 'min:1', 'max:12'],
            'occurrences' => ['required', 'integer', 'min:1', 'max:52'],
            'until' => ['nullable', 'date', 'after_or_equal:date'],
        ]);

        $startsAt = Carbon::parse($validated['date'].' '.$validated['startTime']);
        $endsAt = Carbon::parse($validated['date'].' '.$validated['endTime']);

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            $this->addError('endTime', __('The end time must be after the start time.'));

            return;
        }

        $recurrence = (! $this->booking?->exists && $this->frequency !== 'none') ? [
            'frequency' => $this->frequency,
            'interval' => $this->interval,
            'count' => $this->occurrences,
            'until' => $this->until ?: null,
            'days' => array_map('intval', $this->weeklyDays),
        ] : null;

        $attributes = [
            'title' => $validated['title'],
            'purpose' => $validated['purpose'] ?? null,
            'department' => $validated['department'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'recurrence' => $recurrence,
        ];

        try {
            if ($this->booking?->exists) {
                $service->update($this->booking, $attributes, $this->resourceIds, $this->attendeeIds);
                $booking = $this->booking;
                session()->flash('status', __('Booking updated.'));
            } else {
                $booking = $service->create(auth()->user(), $attributes, $this->resourceIds, $this->attendeeIds);
                session()->flash('status', __('Booking created.'));
            }
        } catch (BookingConflictException|BookingException $exception) {
            $this->addError('general', $exception->getMessage());

            return;
        }

        $this->redirectRoute('bookings.show', $booking, navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $conflicts = collect();

        if ($this->date !== '' && count($this->resourceIds) > 0) {
            try {
                $startsAt = Carbon::parse($this->date.' '.$this->startTime);
                $endsAt = Carbon::parse($this->date.' '.$this->endTime);

                if ($endsAt->greaterThan($startsAt)) {
                    $resources = Resource::whereIn('id', $this->resourceIds)->get();
                    $conflicts = app(BookingAvailability::class)
                        ->conflictsFor($resources, $startsAt, $endsAt, $this->booking?->id)
                        ->map(fn (BookingConflictException $exception): string => $exception->getMessage());
                }
            } catch (\Throwable) {
                $conflicts = collect();
            }
        }

        return [
            'resources' => Resource::bookable()->with('type')->orderBy('name')->get(),
            'users' => User::whereKeyNot(auth()->id())->orderBy('name')->get(),
            'conflicts' => $conflicts,
            'statuses' => BookingStatus::cases(),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <x-page-header
                :title="$booking?->exists ? __('Edit booking') : __('New booking')"
                :subtitle="$booking?->exists ? $booking->reference : __('Pick one or more resources and a time that works.')"
            />

            @if ($errors->has('general'))
                <div role="alert" class="alert alert-error">
                    <span>{{ $errors->first('general') }}</span>
                </div>
            @endif

            <form wire:submit="save" class="space-y-6">
                <div class="card space-y-4 bg-base-100 p-6">
                    <h3 class="text-lg font-medium text-base-content">{{ __('Details') }}</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="title" :value="__('Title')" />
                            <x-text-input wire:model="title" id="title" type="text" class="mt-1 block w-full" placeholder="{{ __('e.g. Client presentation') }}" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="department" :value="__('Department')" />
                            <x-text-input wire:model="department" id="department" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('department')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="purpose" :value="__('Purpose / notes')" />
                            <textarea wire:model="purpose" id="purpose" rows="3" class="textarea textarea-bordered mt-1 block w-full rounded-md border-base-300 focus:border-primary focus:ring-primary"></textarea>
                            <x-input-error :messages="$errors->get('purpose')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="card space-y-4 bg-base-100 p-6">
                    <h3 class="text-lg font-medium text-base-content">{{ __('When') }}</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="date" :value="__('Date')" />
                            <x-text-input wire:model.live.debounce.500ms="date" id="date" type="date" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="startTime" :value="__('Start')" />
                            <x-text-input wire:model.live.debounce.500ms="startTime" id="startTime" type="time" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('startTime')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="endTime" :value="__('End')" />
                            <x-text-input wire:model.live.debounce.500ms="endTime" id="endTime" type="time" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('endTime')" class="mt-2" />
                        </div>
                    </div>

                    @unless ($booking?->exists)
                    <div class="grid grid-cols-1 gap-4 border-t border-base-200 pt-4 sm:grid-cols-4">
                        <div>
                            <x-input-label for="frequency" :value="__('Repeat')" />
                            <select wire:model.live="frequency" id="frequency" class="select select-bordered mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary">
                                <option value="none">{{ __('Does not repeat') }}</option>
                                <option value="daily">{{ __('Daily') }}</option>
                                <option value="weekly">{{ __('Weekly') }}</option>
                                <option value="monthly">{{ __('Monthly') }}</option>
                            </select>
                        </div>

                        @if ($frequency !== 'none')
                            <div>
                                <x-input-label for="interval" :value="__('Every')" />
                                <x-text-input wire:model="interval" id="interval" type="number" min="1" max="12" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('interval')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="occurrences" :value="__('Occurrences')" />
                                <x-text-input wire:model="occurrences" id="occurrences" type="number" min="1" max="52" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('occurrences')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="until" :value="__('Until (optional)')" />
                                <x-text-input wire:model="until" id="until" type="date" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('until')" class="mt-2" />
                            </div>

                            @if ($frequency === 'weekly')
                                <div class="sm:col-span-4">
                                    <x-input-label :value="__('On days')" />
                                    <div class="mt-2 flex flex-wrap gap-3">
                                        @foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $value => $label)
                                            <label class="flex items-center gap-1.5">
                                                <input type="checkbox" wire:model="weeklyDays" value="{{ $value }}" class="rounded border-base-300 text-primary focus:ring-primary">
                                                <span class="text-sm text-base-content/80">{{ __($label) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                    @else
                        <p class="border-t border-base-200 pt-4 text-xs text-base-content/50">{{ __('Recurrence cannot be changed after creation. Editing applies to this occurrence only.') }}</p>
                    @endunless
                </div>

                <div class="card space-y-4 bg-base-100 p-6">
                    <div>
                        <h3 class="text-lg font-medium text-base-content">{{ __('Resources') }}</h3>
                        <p class="mt-1 text-sm text-base-content/60">{{ __('Select every resource this booking needs.') }}</p>
                    </div>

                    @error('resourceIds')
                        <p class="text-sm text-error">{{ $message }}</p>
                    @enderror

                    <div class="grid max-h-72 grid-cols-1 gap-2 overflow-y-auto sm:grid-cols-2">
                        @foreach ($resources as $resource)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-base-300 p-3 transition hover:border-primary/40 has-[:checked]:border-primary has-[:checked]:bg-primary/5" wire:key="resource-option-{{ $resource->id }}">
                                <input type="checkbox" wire:model.live.debounce.500ms="resourceIds" value="{{ $resource->id }}" class="mt-0.5 rounded border-base-300 text-primary focus:ring-primary">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-base-content">{{ $resource->name }}</span>
                                    <span class="block truncate text-xs text-base-content/55">{{ $resource->type?->name ?? $resource->code }} · {{ $resource->location ?? '—' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('resourceIds.*')" class="mt-2" />
                </div>

                @if ($conflicts->isNotEmpty())
                    <div role="alert" class="alert alert-warning items-start">
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                        <div>
                            <h3 class="text-sm font-semibold">{{ __('Conflicts detected') }}</h3>
                            <ul class="mt-1 list-disc space-y-0.5 ps-4 text-sm">
                                @foreach ($conflicts as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @elseif (count($resourceIds) > 0 && $date !== '' && $endTime > $startTime)
                    <div role="alert" class="alert alert-success">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                        <span>{{ __('All selected resources are available for this time.') }}</span>
                    </div>
                @endif

                <div class="card space-y-4 bg-base-100 p-6">
                    <div>
                        <h3 class="text-lg font-medium text-base-content">{{ __('People') }}</h3>
                        <p class="mt-1 text-sm text-base-content/60">{{ __('Invite attendees (optional).') }}</p>
                    </div>

                    <label for="attendeeIds" class="sr-only">{{ __('Attendees') }}</label>
                    <select wire:model="attendeeIds" id="attendeeIds" multiple size="6" class="select select-bordered h-auto w-full border-base-300 focus:border-primary focus:ring-primary">
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('attendeeIds.*')" class="mt-2" />
                </div>

                <div class="flex items-center gap-3">
                    <x-primary-button>{{ $booking?->exists ? __('Save changes') : __('Create booking') }}</x-primary-button>
                    <a href="{{ $booking?->exists ? route('bookings.show', $booking) : route('bookings.index') }}" wire:navigate class="text-sm text-base-content/70 hover:text-base-content">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
