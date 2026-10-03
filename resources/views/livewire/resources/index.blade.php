<?php

use App\Enums\ResourceStatus;
use App\Models\Resource;
use App\Models\ResourceType;
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
    public ?int $type = null;

    #[Url(history: true)]
    public string $status = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'type', 'status']);
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $resource = Resource::findOrFail($id);

        Gate::authorize('delete', $resource);

        $resource->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $resources = Resource::query()
            ->with(['type', 'manager'])
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('location', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->type, fn ($query) => $query->where('resource_type_id', $this->type))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->orderBy('name')
            ->paginate(15);

        return [
            'resources' => $resources,
            'types' => ResourceType::orderBy('name')->get(),
            'statuses' => ResourceStatus::cases(),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Resources')" :subtitle="__('Everything bookable: rooms, vehicles, equipment, people and facilities.')">
            <x-slot name="actions">
                <a href="{{ route('calendar') }}" wire:navigate class="btn btn-outline btn-sm rounded-xl">{{ __('Calendar') }}</a>
                @can('create', \App\Models\Resource::class)
                    <a href="{{ route('resources.create') }}" wire:navigate class="btn btn-primary btn-sm rounded-xl">{{ __('New resource') }}</a>
                @endcan
            </x-slot>
        </x-page-header>

        <div class="card bg-base-100 p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <label for="resource-search" class="sr-only">{{ __('Search resources') }}</label>
                    <x-text-input wire:model.live.debounce.300ms="search" id="resource-search" type="search" class="block w-full" placeholder="{{ __('Search name, code, location…') }}" />
                </div>

                <label for="resource-type-filter" class="sr-only">{{ __('Type') }}</label>
                <select wire:model.live="type" id="resource-type-filter" class="select select-bordered w-full border-base-300 focus:border-primary focus:ring-primary">
                    <option value="">{{ __('All types') }}</option>
                    @foreach ($types as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>

                <label for="resource-status-filter" class="sr-only">{{ __('Status') }}</label>
                <select wire:model.live="status" id="resource-status-filter" class="select select-bordered w-full border-base-300 focus:border-primary focus:ring-primary">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-base-200 pt-3">
                <p class="text-xs text-base-content/55" aria-live="polite">
                    {{ __('Showing :from–:to of :total resources', ['from' => $resources->firstItem() ?? 0, 'to' => $resources->lastItem() ?? 0, 'total' => $resources->total()]) }}
                    <span wire:loading class="ms-1 text-primary">{{ __('Updating…') }}</span>
                </p>

                @if ($search !== '' || $type || $status !== '')
                    <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-xs rounded-lg text-base-content/70">{{ __('Clear filters') }}</button>
                @endif
            </div>
        </div>

        <x-data-table :paginator="$resources">
            <x-slot name="table">
                <table class="table table-sm">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Code') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Type') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Location') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Approval') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300">
                        @forelse ($resources as $resource)
                            <tr wire:key="resource-{{ $resource->id }}" class="hover:bg-base-200">
                                <td class="px-4 py-3 font-mono text-sm text-base-content">{{ $resource->code }}</td>
                                <td class="px-4 py-3 text-sm text-base-content">
                                    <a href="{{ route('resources.show', $resource) }}" wire:navigate class="hover:text-primary">{{ $resource->name }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $resource->type?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $resource->location ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $resource->approval_mode->label() }}</td>
                                <td class="px-4 py-3 text-sm"><x-status-badge :status="$resource->status" /></td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <a href="{{ route('resources.show', $resource) }}" wire:navigate class="text-primary hover:opacity-80">{{ __('View') }}</a>
                                    @can('update', $resource)
                                        <a href="{{ route('resources.edit', $resource) }}" wire:navigate class="ms-3 text-base-content/70 hover:text-base-content">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('delete', $resource)
                                        <button wire:click="delete({{ $resource->id }})" wire:confirm="{{ __('Delete this resource?') }}" class="ms-3 text-error hover:opacity-80">{{ __('Delete') }}</button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-base-content/60">{{ __('No resources found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-slot>

            <x-slot name="cards">
                <ul class="divide-y divide-base-300">
                    @forelse ($resources as $resource)
                        <li class="space-y-2 p-4" wire:key="resource-card-{{ $resource->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('resources.show', $resource) }}" wire:navigate class="font-medium text-base-content hover:text-primary">{{ $resource->name }}</a>
                                    <p class="font-mono text-xs text-base-content/50">{{ $resource->code }}</p>
                                </div>
                                <x-status-badge :status="$resource->status" />
                            </div>

                            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-base-content/60">
                                <div><dt class="inline text-base-content/40">{{ __('Type') }}:</dt> {{ $resource->type?->name ?? '—' }}</div>
                                <div><dt class="inline text-base-content/40">{{ __('Location') }}:</dt> {{ $resource->location ?? '—' }}</div>
                                <div><dt class="inline text-base-content/40">{{ __('Approval') }}:</dt> {{ $resource->approval_mode->label() }}</div>
                            </dl>

                            <div class="flex items-center gap-4 pt-1 text-sm">
                                <a href="{{ route('resources.show', $resource) }}" wire:navigate class="text-primary hover:opacity-80">{{ __('View') }}</a>
                                @can('update', $resource)
                                    <a href="{{ route('resources.edit', $resource) }}" wire:navigate class="text-base-content/70 hover:text-base-content">{{ __('Edit') }}</a>
                                @endcan
                                @can('delete', $resource)
                                    <button wire:click="delete({{ $resource->id }})" wire:confirm="{{ __('Delete this resource?') }}" class="text-error hover:opacity-80">{{ __('Delete') }}</button>
                                @endcan
                            </div>
                        </li>
                    @empty
                        <li class="p-8 text-center text-sm text-base-content/60">{{ __('No resources found.') }}</li>
                    @endforelse
                </ul>
            </x-slot>
        </x-data-table>
    </div>
</div>
