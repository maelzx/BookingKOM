<?php

use App\Models\ResourceType;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public bool $requires_approval = false;

    public int $sort_order = 0;

    public function edit(int $id): void
    {
        $type = ResourceType::findOrFail($id);
        Gate::authorize('update', $type);

        $this->editingId = $type->id;
        $this->name = $type->name;
        $this->description = (string) $type->description;
        $this->requires_approval = $type->requires_approval;
        $this->sort_order = $type->sort_order;
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'description', 'requires_approval', 'sort_order']);
    }

    public function save(): void
    {
        $type = $this->editingId ? ResourceType::findOrFail($this->editingId) : new ResourceType;

        $type->exists ? Gate::authorize('update', $type) : Gate::authorize('create', ResourceType::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'requires_approval' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:999'],
        ]);

        $type->fill($validated)->save();

        $this->cancel();
        session()->flash('status', __('Resource type saved.'));
    }

    public function delete(int $id): void
    {
        $type = ResourceType::findOrFail($id);
        Gate::authorize('delete', $type);

        $type->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'types' => ResourceType::withCount('resources')->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Resource types')" :subtitle="__('Categories used to group bookable resources.')" />

        @if (session('status'))
            <div role="alert" class="alert alert-success"><span>{{ session('status') }}</span></div>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="card bg-base-100 p-5 sm:p-6">
                <h2 class="text-base font-semibold text-base-content">{{ $editingId ? __('Edit type') : __('New type') }}</h2>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="type-name" :value="__('Name')" />
                        <x-text-input wire:model="name" id="type-name" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="type-description" :value="__('Description')" />
                        <textarea wire:model="description" id="type-description" rows="2" class="textarea textarea-bordered mt-1 block w-full rounded-md border-base-300 focus:border-primary focus:ring-primary"></textarea>
                    </div>
                    <div>
                        <x-input-label for="type-sort" :value="__('Sort order')" />
                        <x-text-input wire:model="sort_order" id="type-sort" type="number" min="0" class="mt-1 block w-full" />
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="requires_approval" class="rounded border-base-300 text-primary focus:ring-primary">
                        <span class="text-sm text-base-content/80">{{ __('Bookings require approval by default') }}</span>
                    </label>

                    <div class="flex items-center gap-3">
                        <x-primary-button>{{ $editingId ? __('Update') : __('Add type') }}</x-primary-button>
                        @if ($editingId)
                            <button type="button" wire:click="cancel" class="text-sm text-base-content/70 hover:text-base-content">{{ __('Cancel') }}</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2">
                <div class="card overflow-hidden bg-base-100">
                    <table class="table table-sm">
                        <thead class="bg-base-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Approval') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Resources') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-300">
                            @forelse ($types as $type)
                                <tr wire:key="type-{{ $type->id }}">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-base-content">{{ $type->name }}</p>
                                        @if ($type->description)
                                            <p class="text-xs text-base-content/50">{{ $type->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-base-content/60">{{ $type->requires_approval ? __('Required') : __('None') }}</td>
                                    <td class="px-4 py-3 text-right text-sm tabular-nums text-base-content/70">{{ $type->resources_count }}</td>
                                    <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                        @can('update', $type)
                                            <button wire:click="edit({{ $type->id }})" class="text-base-content/70 hover:text-base-content">{{ __('Edit') }}</button>
                                        @endcan
                                        @can('delete', $type)
                                            <button wire:click="delete({{ $type->id }})" wire:confirm="{{ __('Delete this type?') }}" class="ms-3 text-error hover:opacity-80">{{ __('Delete') }}</button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-base-content/60">{{ __('No resource types yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
