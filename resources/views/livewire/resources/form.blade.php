<?php

use App\Enums\ApprovalMode;
use App\Enums\ResourceStatus;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public ?Resource $resource = null;

    public ?int $resource_type_id = null;

    public string $name = '';

    public string $code = '';

    public string $description = '';

    public string $location = '';

    public string $status = 'active';

    public ?int $capacity = null;

    public string $approval_mode = 'none';

    public ?int $manager_id = null;

    public ?int $min_duration_minutes = 15;

    public ?int $max_duration_minutes = 480;

    public ?int $buffer_minutes = 0;

    public ?int $max_advance_days = null;

    public ?int $max_bookings_per_day = null;

    public ?string $available_from = null;

    public ?string $available_to = null;

    /**
     * @var array<int, int>
     */
    public array $available_days = [1, 2, 3, 4, 5];

    public bool $is_bookable = true;

    public mixed $image = null;

    public ?string $existingImage = null;

    /**
     * @var array<int, array{key: string, value: string}>
     */
    public array $customFields = [];

    public function mount(?Resource $resource = null): void
    {
        $this->resource = $resource;

        if ($resource?->exists) {
            Gate::authorize('update', $resource);

            $this->resource_type_id = $resource->resource_type_id;
            $this->name = $resource->name;
            $this->code = $resource->code;
            $this->description = (string) $resource->description;
            $this->location = (string) $resource->location;
            $this->status = $resource->status->value;
            $this->capacity = $resource->capacity;
            $this->approval_mode = $resource->approval_mode->value;
            $this->manager_id = $resource->manager_id;
            $this->min_duration_minutes = (int) $resource->rule('min_duration_minutes', 15);
            $this->max_duration_minutes = (int) $resource->rule('max_duration_minutes', 480);
            $this->buffer_minutes = (int) $resource->rule('buffer_minutes', 0);
            $this->max_advance_days = $resource->rule('max_advance_days');
            $this->max_bookings_per_day = $resource->rule('max_bookings_per_day');
            $this->available_from = $resource->available_from ? substr($resource->available_from, 0, 5) : null;
            $this->available_to = $resource->available_to ? substr($resource->available_to, 0, 5) : null;
            $this->available_days = array_map('intval', (array) ($resource->available_days ?? [1, 2, 3, 4, 5]));
            $this->is_bookable = (bool) $resource->is_bookable;
            $this->existingImage = $resource->image_path;
            $this->customFields = collect($resource->custom_fields ?? [])
                ->map(fn ($value, $key): array => ['key' => (string) $key, 'value' => (string) $value])
                ->values()
                ->all();

            return;
        }

        Gate::authorize('create', Resource::class);
    }

    public function addCustomField(): void
    {
        $this->customFields[] = ['key' => '', 'value' => ''];
    }

    public function removeCustomField(int $index): void
    {
        unset($this->customFields[$index]);
        $this->customFields = array_values($this->customFields);
    }

    public function save(): void
    {
        $this->resource?->exists
            ? Gate::authorize('update', $this->resource)
            : Gate::authorize('create', Resource::class);

        $validated = $this->validate([
            'resource_type_id' => ['nullable', 'integer', Rule::exists('resource_types', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('resources', 'code')->ignore($this->resource?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(ResourceStatus::values())],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'approval_mode' => ['required', Rule::in(ApprovalMode::values())],
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'min_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'max_duration_minutes' => ['nullable', 'integer', 'min:5', 'max:43200'],
            'buffer_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'max_advance_days' => ['nullable', 'integer', 'min:1', 'max:730'],
            'max_bookings_per_day' => ['nullable', 'integer', 'min:1', 'max:100'],
            'available_from' => ['nullable', 'date_format:H:i'],
            'available_to' => ['nullable', 'date_format:H:i', 'after:available_from'],
            'available_days' => ['array'],
            'available_days.*' => ['integer', 'between:1,7'],
            'is_bookable' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
            'customFields' => ['array'],
            'customFields.*.key' => ['nullable', 'string', 'max:100'],
            'customFields.*.value' => ['nullable', 'string', 'max:1000'],
        ]);

        $custom = [];

        foreach ($this->customFields as $field) {
            $key = trim((string) ($field['key'] ?? ''));

            if ($key !== '') {
                $custom[$key] = (string) ($field['value'] ?? '');
            }
        }

        $validated['custom_fields'] = $custom;
        $validated['booking_rules'] = array_filter([
            'min_duration_minutes' => $this->min_duration_minutes,
            'max_duration_minutes' => $this->max_duration_minutes,
            'buffer_minutes' => $this->buffer_minutes,
            'max_advance_days' => $this->max_advance_days,
            'max_bookings_per_day' => $this->max_bookings_per_day,
        ], fn ($value) => $value !== null);

        if ($this->image) {
            $validated['image_path'] = $this->image->store('resource-images', 'public');

            if ($this->existingImage) {
                Storage::disk('public')->delete($this->existingImage);
            }
        }

        unset($validated['image'], $validated['customFields']);

        if ($this->resource?->exists) {
            $this->resource->update($validated);
            $resource = $this->resource;
        } else {
            $validated['created_by'] = auth()->id();
            $resource = Resource::create($validated);
        }

        session()->flash('status', __('Resource saved.'));

        $this->redirectRoute('resources.show', $resource, navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'types' => ResourceType::orderBy('name')->get(),
            'managers' => User::whereIn('role', [\App\Enums\Role::Admin->value, \App\Enums\Role::ResourceManager->value])->orderBy('name')->get(),
            'statuses' => ResourceStatus::cases(),
            'approvalModes' => ApprovalMode::cases(),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl space-y-6">
            <x-page-header
                :title="$resource?->exists ? __('Edit resource') : __('New resource')"
                :subtitle="$resource?->exists ? $resource->code : __('Leave the code blank to auto-generate one.')"
            />

            <form wire:submit="save" class="space-y-6">
                <div class="card space-y-4 bg-base-100 p-6">
                    <h3 class="text-lg font-medium text-base-content">{{ __('Identity') }}</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="code" :value="__('Code')" />
                            <x-text-input wire:model="code" id="code" type="text" class="mt-1 block w-full" placeholder="RES-0001" />
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="resource_type_id" :value="__('Type')" />
                            <select wire:model="resource_type_id" id="resource_type_id" class="select select-bordered mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary">
                                <option value="">{{ __('— None —') }}</option>
                                @foreach ($types as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('resource_type_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="manager_id" :value="__('Owner / manager')" />
                            <select wire:model="manager_id" id="manager_id" class="select select-bordered mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary">
                                <option value="">{{ __('— None —') }}</option>
                                @foreach ($managers as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('manager_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="location" :value="__('Location')" />
                            <x-text-input wire:model="location" id="location" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('location')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="capacity" :value="__('Capacity')" />
                            <x-text-input wire:model="capacity" id="capacity" type="number" min="1" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="status" :value="__('Status')" />
                            <select wire:model="status" id="status" class="select select-bordered mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary">
                                @foreach ($statuses as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="approval_mode" :value="__('Approval')" />
                            <select wire:model="approval_mode" id="approval_mode" class="select select-bordered mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary">
                                @foreach ($approvalModes as $option)
                                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('approval_mode')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea wire:model="description" id="description" rows="3" class="textarea textarea-bordered mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md"></textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="image" :value="__('Photo')" />
                            <input wire:model="image" id="image" type="file" accept="image/*" class="mt-1 block w-full text-sm text-base-content/70" />
                            <x-input-error :messages="$errors->get('image')" class="mt-2" />
                            @if ($image)
                                <img src="{{ $image->temporaryUrl() }}" class="mt-3 h-24 w-24 rounded object-cover" alt="">
                            @elseif ($existingImage)
                                <img src="{{ Storage::disk('public')->url($existingImage) }}" class="mt-3 h-24 w-24 rounded object-cover" alt="">
                            @endif
                        </div>

                        <div class="flex items-end">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" wire:model="is_bookable" class="rounded border-base-300 text-primary focus:ring-primary">
                                <span class="text-sm text-base-content/80">{{ __('Bookable') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card space-y-4 bg-base-100 p-6">
                    <div>
                        <h3 class="text-lg font-medium text-base-content">{{ __('Booking rules') }}</h3>
                        <p class="mt-1 text-sm text-base-content/60">{{ __('Leave a field blank to use the organisation defaults.') }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="min_duration_minutes" :value="__('Min duration (min)')" />
                            <x-text-input wire:model="min_duration_minutes" id="min_duration_minutes" type="number" min="5" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('min_duration_minutes')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="max_duration_minutes" :value="__('Max duration (min)')" />
                            <x-text-input wire:model="max_duration_minutes" id="max_duration_minutes" type="number" min="5" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('max_duration_minutes')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="buffer_minutes" :value="__('Buffer between bookings (min)')" />
                            <x-text-input wire:model="buffer_minutes" id="buffer_minutes" type="number" min="0" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('buffer_minutes')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="max_advance_days" :value="__('Max advance (days)')" />
                            <x-text-input wire:model="max_advance_days" id="max_advance_days" type="number" min="1" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('max_advance_days')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="max_bookings_per_day" :value="__('Max bookings per day')" />
                            <x-text-input wire:model="max_bookings_per_day" id="max_bookings_per_day" type="number" min="1" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('max_bookings_per_day')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="card space-y-4 bg-base-100 p-6">
                    <h3 class="text-lg font-medium text-base-content">{{ __('Availability') }}</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="available_from" :value="__('Available from')" />
                            <x-text-input wire:model="available_from" id="available_from" type="time" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('available_from')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="available_to" :value="__('Available to')" />
                            <x-text-input wire:model="available_to" id="available_to" type="time" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('available_to')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label :value="__('Available days')" />
                        <div class="mt-2 flex flex-wrap gap-3">
                            @foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $value => $label)
                                <label class="flex items-center gap-1.5">
                                    <input type="checkbox" wire:model="available_days" value="{{ $value }}" class="rounded border-base-300 text-primary focus:ring-primary">
                                    <span class="text-sm text-base-content/80">{{ __($label) }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('available_days')" class="mt-2" />
                    </div>
                </div>

                <div class="card space-y-4 bg-base-100 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-base-content">{{ __('Custom fields') }}</h3>
                        <button type="button" wire:click="addCustomField" class="text-sm text-primary hover:opacity-80">{{ __('+ Add field') }}</button>
                    </div>

                    @forelse ($customFields as $index => $field)
                        <div class="flex items-start gap-3" wire:key="custom-field-{{ $index }}">
                            <div class="w-1/3">
                                <x-text-input wire:model="customFields.{{ $index }}.key" type="text" class="block w-full" placeholder="{{ __('Label') }}" />
                            </div>
                            <div class="flex-1">
                                <x-text-input wire:model="customFields.{{ $index }}.value" type="text" class="block w-full" placeholder="{{ __('Value') }}" />
                            </div>
                            <button type="button" wire:click="removeCustomField({{ $index }})" class="mt-2 text-sm text-error hover:opacity-80">{{ __('Remove') }}</button>
                        </div>
                    @empty
                        <p class="text-sm text-base-content/60">{{ __('No custom fields. Add any extra attributes you need.') }}</p>
                    @endforelse
                </div>

                <div class="flex items-center gap-3">
                    <x-primary-button>{{ __('Save resource') }}</x-primary-button>
                    <a href="{{ route('resources.index') }}" wire:navigate class="text-sm text-base-content/70 hover:text-base-content">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
