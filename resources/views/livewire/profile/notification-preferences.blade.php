<?php

use Livewire\Volt\Component;

new class extends Component
{
    public bool $notify_booking_created = true;

    public bool $notify_approval_required = true;

    public bool $notify_booking_decisions = true;

    public bool $notify_booking_reminders = true;

    public function mount(): void
    {
        $user = auth()->user();

        $this->notify_booking_created = (bool) $user->notify_booking_created;
        $this->notify_approval_required = (bool) $user->notify_approval_required;
        $this->notify_booking_decisions = (bool) $user->notify_booking_decisions;
        $this->notify_booking_reminders = (bool) $user->notify_booking_reminders;
    }

    public function update(): void
    {
        $validated = $this->validate([
            'notify_booking_created' => ['boolean'],
            'notify_approval_required' => ['boolean'],
            'notify_booking_decisions' => ['boolean'],
            'notify_booking_reminders' => ['boolean'],
        ]);

        auth()->user()->update($validated);

        $this->dispatch('saved');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-base-content">{{ __('Email notifications') }}</h2>
        <p class="mt-1 text-sm text-base-content/70">{{ __('Choose which booking notifications you receive.') }}</p>
    </header>

    <form wire:submit="update" class="mt-6 space-y-3">
        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="notify_booking_created" class="rounded border-base-300 text-primary focus:ring-primary">
            <span class="text-sm text-base-content/80">{{ __('Booking confirmations and updates') }}</span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="notify_approval_required" class="rounded border-base-300 text-primary focus:ring-primary">
            <span class="text-sm text-base-content/80">{{ __('Approval requests awaiting my decision') }}</span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="notify_booking_decisions" class="rounded border-base-300 text-primary focus:ring-primary">
            <span class="text-sm text-base-content/80">{{ __('Approval decisions on my bookings') }}</span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="notify_booking_reminders" class="rounded border-base-300 text-primary focus:ring-primary">
            <span class="text-sm text-base-content/80">{{ __('Upcoming booking reminders') }}</span>
        </label>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
            <x-action-message on="saved">{{ __('Saved.') }}</x-action-message>
        </div>
    </form>
</section>
