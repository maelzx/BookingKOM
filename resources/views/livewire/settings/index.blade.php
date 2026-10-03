<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $org_name = '';

    public string $org_timezone = 'UTC';

    /**
     * @var array<int, int>
     */
    public array $working_days = [1, 2, 3, 4, 5];

    public string $working_hours_start = '08:00';

    public string $working_hours_end = '18:00';

    public int $default_booking_minutes = 60;

    public int $min_booking_minutes = 15;

    public int $max_booking_minutes = 480;

    public int $advance_booking_days = 90;

    public int $min_notice_minutes = 0;

    public int $cancellation_notice_hours = 2;

    public string $resource_code_prefix = 'RES';

    public string $booking_reference_prefix = 'BK';

    public int $reminder_lead_minutes = 60;

    public bool $notifications_enabled = true;

    public bool $qr_enabled = true;

    public function mount(): void
    {
        Gate::authorize('manage-settings');

        $this->org_name = (string) Setting::get('org_name', 'BookingKOM');
        $this->org_timezone = (string) Setting::get('org_timezone', config('app.timezone'));
        $this->working_days = array_map('intval', (array) Setting::get('working_days', [1, 2, 3, 4, 5]));
        $this->working_hours_start = (string) Setting::get('working_hours_start', '08:00');
        $this->working_hours_end = (string) Setting::get('working_hours_end', '18:00');
        $this->default_booking_minutes = (int) Setting::get('default_booking_minutes', 60);
        $this->min_booking_minutes = (int) Setting::get('min_booking_minutes', 15);
        $this->max_booking_minutes = (int) Setting::get('max_booking_minutes', 480);
        $this->advance_booking_days = (int) Setting::get('advance_booking_days', 90);
        $this->min_notice_minutes = (int) Setting::get('min_notice_minutes', 0);
        $this->cancellation_notice_hours = (int) Setting::get('cancellation_notice_hours', 2);
        $this->resource_code_prefix = (string) Setting::get('resource_code_prefix', 'RES');
        $this->booking_reference_prefix = (string) Setting::get('booking_reference_prefix', 'BK');
        $this->reminder_lead_minutes = (int) Setting::get('reminder_lead_minutes', 60);
        $this->notifications_enabled = (bool) Setting::get('notifications_enabled', true);
        $this->qr_enabled = (bool) Setting::get('qr_enabled', true);
    }

    public function save(): void
    {
        Gate::authorize('manage-settings');

        $validated = $this->validate([
            'org_name' => ['required', 'string', 'max:255'],
            'org_timezone' => ['required', 'string', 'timezone'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['integer', 'between:1,7'],
            'working_hours_start' => ['required', 'date_format:H:i'],
            'working_hours_end' => ['required', 'date_format:H:i', 'after:working_hours_start'],
            'default_booking_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'min_booking_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'max_booking_minutes' => ['required', 'integer', 'min:5', 'max:43200'],
            'advance_booking_days' => ['required', 'integer', 'min:1', 'max:730'],
            'min_notice_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'cancellation_notice_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'resource_code_prefix' => ['required', 'string', 'max:10', 'alpha_num'],
            'booking_reference_prefix' => ['required', 'string', 'max:10', 'alpha_num'],
            'reminder_lead_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'notifications_enabled' => ['boolean'],
            'qr_enabled' => ['boolean'],
        ]);

        $types = [
            'working_days' => 'array',
            'default_booking_minutes' => 'integer',
            'min_booking_minutes' => 'integer',
            'max_booking_minutes' => 'integer',
            'advance_booking_days' => 'integer',
            'min_notice_minutes' => 'integer',
            'cancellation_notice_hours' => 'integer',
            'reminder_lead_minutes' => 'integer',
            'notifications_enabled' => 'boolean',
            'qr_enabled' => 'boolean',
        ];

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, $types[$key] ?? 'string');
        }

        $this->dispatch('settings-saved');
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl space-y-6">
            <x-page-header :title="__('Settings')" :subtitle="__('Organisation defaults for bookings, availability and notifications.')" />

            <form wire:submit="save" class="space-y-6">
                <div class="card space-y-4 bg-base-100 p-6">
                    <h3 class="text-lg font-medium text-base-content">{{ __('Organisation') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="org_name" :value="__('Organisation name')" />
                            <x-text-input wire:model="org_name" id="org_name" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('org_name')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="org_timezone" :value="__('Timezone')" />
                            <x-text-input wire:model="org_timezone" id="org_timezone" type="text" class="mt-1 block w-full" placeholder="Asia/Kuala_Lumpur" />
                            <x-input-error :messages="$errors->get('org_timezone')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="card space-y-4 bg-base-100 p-6">
                    <h3 class="text-lg font-medium text-base-content">{{ __('Working hours') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="working_hours_start" :value="__('Start')" />
                            <x-text-input wire:model="working_hours_start" id="working_hours_start" type="time" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('working_hours_start')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="working_hours_end" :value="__('End')" />
                            <x-text-input wire:model="working_hours_end" id="working_hours_end" type="time" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('working_hours_end')" class="mt-2" />
                        </div>
                    </div>
                    <div>
                        <x-input-label :value="__('Working days')" />
                        <div class="mt-2 flex flex-wrap gap-3">
                            @foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $value => $label)
                                <label class="flex items-center gap-1.5">
                                    <input type="checkbox" wire:model="working_days" value="{{ $value }}" class="rounded border-base-300 text-primary focus:ring-primary">
                                    <span class="text-sm text-base-content/80">{{ __($label) }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('working_days')" class="mt-2" />
                    </div>
                </div>

                <div class="card space-y-4 bg-base-100 p-6">
                    <h3 class="text-lg font-medium text-base-content">{{ __('Booking rules') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="default_booking_minutes" :value="__('Default duration (min)')" />
                            <x-text-input wire:model="default_booking_minutes" id="default_booking_minutes" type="number" min="5" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('default_booking_minutes')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="min_booking_minutes" :value="__('Minimum duration (min)')" />
                            <x-text-input wire:model="min_booking_minutes" id="min_booking_minutes" type="number" min="5" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('min_booking_minutes')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="max_booking_minutes" :value="__('Maximum duration (min)')" />
                            <x-text-input wire:model="max_booking_minutes" id="max_booking_minutes" type="number" min="5" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('max_booking_minutes')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="advance_booking_days" :value="__('Advance booking limit (days)')" />
                            <x-text-input wire:model="advance_booking_days" id="advance_booking_days" type="number" min="1" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('advance_booking_days')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="min_notice_minutes" :value="__('Minimum notice (min)')" />
                            <x-text-input wire:model="min_notice_minutes" id="min_notice_minutes" type="number" min="0" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('min_notice_minutes')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="cancellation_notice_hours" :value="__('Cancellation notice (hours)')" />
                            <x-text-input wire:model="cancellation_notice_hours" id="cancellation_notice_hours" type="number" min="0" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('cancellation_notice_hours')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="card space-y-4 bg-base-100 p-6">
                    <h3 class="text-lg font-medium text-base-content">{{ __('Reference formats') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="resource_code_prefix" :value="__('Resource code prefix')" />
                            <x-text-input wire:model="resource_code_prefix" id="resource_code_prefix" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('resource_code_prefix')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="booking_reference_prefix" :value="__('Booking reference prefix')" />
                            <x-text-input wire:model="booking_reference_prefix" id="booking_reference_prefix" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('booking_reference_prefix')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="card space-y-4 bg-base-100 p-6">
                    <h3 class="text-lg font-medium text-base-content">{{ __('Notifications & QR') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="reminder_lead_minutes" :value="__('Reminder lead time (min)')" />
                            <x-text-input wire:model="reminder_lead_minutes" id="reminder_lead_minutes" type="number" min="0" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('reminder_lead_minutes')" class="mt-2" />
                        </div>
                        <div class="flex flex-col justify-center gap-3">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="notifications_enabled" class="rounded border-base-300 text-primary focus:ring-primary">
                                <span class="text-sm text-base-content/80">{{ __('Send booking notifications') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="qr_enabled" class="rounded border-base-300 text-primary focus:ring-primary">
                                <span class="text-sm text-base-content/80">{{ __('Enable QR codes for resources') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button>{{ __('Save settings') }}</x-primary-button>
                    <x-action-message on="settings-saved">{{ __('Saved.') }}</x-action-message>
                </div>
            </form>
        </div>
    </div>
</div>
