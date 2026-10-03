<?php

use App\Enums\BlockType;
use App\Enums\BookingStatus;
use App\Models\Resource;
use App\Models\ResourceBlockedPeriod;
use App\Services\BookingAvailability;
use App\Support\ResourceQrCode;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public Resource $resource;

    public string $availabilityDate = '';

    public string $blockType = 'blocked';

    public string $blockStartsAt = '';

    public string $blockEndsAt = '';

    public string $blockReason = '';

    public mixed $attachment = null;

    public function mount(Resource $resource): void
    {
        $this->resource = $resource->load(['type', 'manager']);
        $this->availabilityDate = now()->toDateString();
    }

    public function addBlock(): void
    {
        Gate::authorize('manageBlockedPeriods', $this->resource);

        $validated = $this->validate([
            'blockType' => ['required', Rule::in(BlockType::values())],
            'blockStartsAt' => ['required', 'date'],
            'blockEndsAt' => ['required', 'date', 'after:blockStartsAt'],
            'blockReason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->resource->blockedPeriods()->create([
            'type' => $validated['blockType'],
            'starts_at' => $validated['blockStartsAt'],
            'ends_at' => $validated['blockEndsAt'],
            'reason' => $validated['blockReason'] ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->reset(['blockStartsAt', 'blockEndsAt', 'blockReason']);
        $this->dispatch('block-added');
    }

    public function removeBlock(int $id): void
    {
        Gate::authorize('manageBlockedPeriods', $this->resource);

        ResourceBlockedPeriod::where('resource_id', $this->resource->id)->whereKey($id)->delete();
    }

    public function uploadAttachment(): void
    {
        Gate::authorize('update', $this->resource);

        $validated = $this->validate([
            'attachment' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png,webp'],
        ]);

        $file = $validated['attachment'];
        $path = $file->store('attachments', 'local');

        $this->resource->attachments()->create([
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        $this->reset('attachment');
        $this->dispatch('attachment-added');
    }

    public function deleteAttachment(int $id): void
    {
        $attachment = $this->resource->attachments()->whereKey($id)->firstOrFail();

        Gate::authorize('delete', $attachment);

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $availability = app(BookingAvailability::class);
        $date = \Illuminate\Support\Carbon::parse($this->availabilityDate ?: 'today');

        return [
            'slots' => $availability->slotsForDate($this->resource, $date),
            'date' => $date,
            'upcoming' => $this->resource->bookings()
                ->where('bookings.starts_at', '>=', now())
                ->whereIn('bookings.status', [BookingStatus::Pending->value, BookingStatus::Approved->value, BookingStatus::Confirmed->value])
                ->with('organiser')
                ->orderBy('bookings.starts_at')
                ->take(8)
                ->get(),
            'blocks' => $this->resource->blockedPeriods()
                ->where('ends_at', '>=', now())
                ->orderBy('starts_at')
                ->get(),
            'blockTypes' => BlockType::cases(),
            'attachments' => $this->resource->attachments()->with('uploader')->latest()->get(),
            'qr' => ResourceQrCode::dataUri($this->resource, 320),
            'scanUrl' => route('scan.show', $this->resource->code),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="$resource->name" :subtitle="$resource->code">
            <x-slot name="actions">
                <a href="{{ route('resources.index') }}" wire:navigate class="btn btn-ghost btn-sm rounded-xl">{{ __('Back') }}</a>
                @can('update', $resource)
                    <a href="{{ route('resources.edit', $resource) }}" wire:navigate class="btn btn-outline btn-sm rounded-xl">{{ __('Edit') }}</a>
                @endcan
                @if ($resource->isBookable())
                    <a href="{{ route('bookings.create', ['resource' => $resource->id]) }}" wire:navigate class="btn btn-primary btn-sm rounded-xl">{{ __('Book resource') }}</a>
                @endif
            </x-slot>
        </x-page-header>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <div class="card bg-base-100 p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <x-status-badge :status="$resource->status" />
                                @if (! $resource->is_bookable)
                                    <span class="badge badge-ghost badge-sm">{{ __('Not bookable') }}</span>
                                @endif
                            </div>
                            <p class="max-w-2xl text-sm leading-6 text-base-content/70">{{ $resource->description ?: __('No description provided.') }}</p>
                        </div>
                        @if ($resource->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($resource->image_path) }}" class="h-24 w-24 rounded-xl object-cover" alt="">
                        @endif
                    </div>

                    <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 border-t border-base-200 pt-5 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Type') }}</dt>
                            <dd class="mt-1 text-base-content">{{ $resource->type?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Location') }}</dt>
                            <dd class="mt-1 text-base-content">{{ $resource->location ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Capacity') }}</dt>
                            <dd class="mt-1 text-base-content">{{ $resource->capacity ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Manager') }}</dt>
                            <dd class="mt-1 text-base-content">{{ $resource->manager?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Approval') }}</dt>
                            <dd class="mt-1 text-base-content">{{ $resource->approval_mode->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Hours') }}</dt>
                            <dd class="mt-1 text-base-content">
                                @php [$from, $to, $days] = $resource->workingWindow(); @endphp
                                {{ \Illuminate\Support\Carbon::parse($from)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($to)->format('H:i') }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="card bg-base-100 p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-base-content">{{ __('Availability') }}</h2>
                            <p class="mt-1 text-sm text-base-content/55">{{ __('30-minute slots for the selected day.') }}</p>
                        </div>
                        <div>
                            <label for="availability-date" class="sr-only">{{ __('Date') }}</label>
                            <input id="availability-date" type="date" wire:model.live="availabilityDate" class="input input-bordered input-sm border-base-300 focus:border-primary focus:ring-primary">
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @forelse ($slots as $slot)
                            <span class="rounded-md px-2 py-1 text-xs font-medium tabular-nums {{ $slot['available'] ? 'bg-success/10 text-success' : 'bg-error/10 text-error line-through' }}" title="{{ $slot['available'] ? __('Available') : __('Unavailable') }}">
                                {{ $slot['start']->format('H:i') }}
                            </span>
                        @empty
                            <p class="text-sm text-base-content/60">{{ __('This resource is not available on the selected day.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="card bg-base-100 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-base-content">{{ __('Upcoming bookings') }}</h2>
                    <ul class="mt-4 divide-y divide-base-200">
                        @forelse ($upcoming as $booking)
                            <li>
                                <a href="{{ route('bookings.show', $booking) }}" wire:navigate class="flex items-center justify-between gap-3 rounded-lg py-3 transition hover:px-2 hover:bg-base-200/70">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-base-content">{{ $booking->title }}</p>
                                        <p class="truncate text-xs text-base-content/55">{{ $booking->organiser?->name }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-xs tabular-nums text-base-content/60">{{ $booking->starts_at->format('d M H:i') }}</p>
                                        <x-status-badge :status="$booking->status" />
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('No upcoming bookings.') }}</li>
                        @endforelse
                    </ul>
                </div>

                <div class="card bg-base-100 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-base-content">{{ __('Attachments') }}</h2>
                    <p class="mt-1 text-sm text-base-content/55">{{ __('Documents, manuals or photos for this resource.') }}</p>

                    <ul class="mt-4 divide-y divide-base-200">
                        @forelse ($attachments as $file)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('attachments.download', $file) }}" class="block truncate text-sm font-medium text-primary hover:opacity-80">{{ $file->original_name }}</a>
                                    <p class="text-xs text-base-content/50">{{ $file->humanSize() }} · {{ $file->uploader?->name ?? '—' }} · {{ $file->created_at->format('d M Y') }}</p>
                                </div>
                                @can('delete', $file)
                                    <button wire:click="deleteAttachment({{ $file->id }})" wire:confirm="{{ __('Delete this attachment?') }}" class="shrink-0 text-sm text-error hover:opacity-80">{{ __('Delete') }}</button>
                                @endcan
                            </li>
                        @empty
                            <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('No attachments.') }}</li>
                        @endforelse
                    </ul>

                    @can('update', $resource)
                        <form wire:submit="uploadAttachment" class="mt-5 space-y-3 border-t border-base-200 pt-5">
                            <div>
                                <x-input-label for="attachment" :value="__('Upload a file')" />
                                <input wire:model="attachment" id="attachment" type="file" class="mt-1 block w-full text-sm text-base-content/70" />
                                <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
                            </div>
                            <div class="flex items-center gap-3">
                                <x-secondary-button type="submit">{{ __('Upload') }}</x-secondary-button>
                                <x-action-message on="attachment-added">{{ __('Uploaded.') }}</x-action-message>
                                <span wire:loading wire:target="attachment" class="text-xs text-primary">{{ __('Uploading…') }}</span>
                            </div>
                        </form>
                    @endcan
                </div>

                @can('manageBlockedPeriods', $resource)
                    <div class="card bg-base-100 p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-base-content">{{ __('Blocked & maintenance periods') }}</h2>

                        <ul class="mt-4 divide-y divide-base-200">
                            @forelse ($blocks as $block)
                                <li class="flex items-center justify-between gap-3 py-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <x-status-badge :status="$block->type" />
                                            <span class="truncate text-sm text-base-content/80">{{ $block->reason ?: '—' }}</span>
                                        </div>
                                        <p class="mt-0.5 text-xs tabular-nums text-base-content/55">
                                            {{ $block->starts_at->format('d M Y H:i') }} – {{ $block->ends_at->format('d M Y H:i') }}
                                        </p>
                                    </div>
                                    <button type="button" wire:click="removeBlock({{ $block->id }})" wire:confirm="{{ __('Remove this blocked period?') }}" class="shrink-0 text-sm text-error hover:opacity-80">{{ __('Remove') }}</button>
                                </li>
                            @empty
                                <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('No blocked periods.') }}</li>
                            @endforelse
                        </ul>

                        <form wire:submit="addBlock" class="mt-5 space-y-3 border-t border-base-200 pt-5">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="blockType" :value="__('Type')" />
                                    <select wire:model="blockType" id="blockType" class="select select-bordered mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary">
                                        @foreach ($blockTypes as $option)
                                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('blockType')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="blockReason" :value="__('Reason')" />
                                    <x-text-input wire:model="blockReason" id="blockReason" type="text" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('blockReason')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="blockStartsAt" :value="__('From')" />
                                    <x-text-input wire:model="blockStartsAt" id="blockStartsAt" type="datetime-local" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('blockStartsAt')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="blockEndsAt" :value="__('To')" />
                                    <x-text-input wire:model="blockEndsAt" id="blockEndsAt" type="datetime-local" class="mt-1 block w-full" />
                                    <x-input-error :messages="$errors->get('blockEndsAt')" class="mt-2" />
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-secondary-button type="submit">{{ __('Add blocked period') }}</x-secondary-button>
                                <x-action-message on="block-added">{{ __('Added.') }}</x-action-message>
                            </div>
                        </form>
                    </div>
                @else
                    @if ($blocks->isNotEmpty())
                        <div class="card bg-base-100 p-5 sm:p-6">
                            <h2 class="text-base font-semibold text-base-content">{{ __('Blocked & maintenance periods') }}</h2>
                            <ul class="mt-4 divide-y divide-base-200">
                                @foreach ($blocks as $block)
                                    <li class="flex items-center justify-between gap-3 py-3">
                                        <x-status-badge :status="$block->type" />
                                        <span class="text-xs tabular-nums text-base-content/55">{{ $block->starts_at->format('d M Y H:i') }} – {{ $block->ends_at->format('d M Y H:i') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endcan
            </div>

            <div class="space-y-5">
                <div class="card bg-base-100 p-5 text-center sm:p-6">
                    <h2 class="text-base font-semibold text-base-content">{{ __('QR code') }}</h2>
                    <p class="mt-1 text-sm text-base-content/55">{{ __('Scan to open the mobile booking page.') }}</p>
                    <img src="{{ $qr }}" alt="{{ __('QR code for :name', ['name' => $resource->name]) }}" class="mx-auto mt-4 h-44 w-44 rounded-xl bg-white p-2">
                    <a href="{{ $qr }}" download="resource-{{ $resource->code }}.png" class="btn btn-outline btn-sm mt-4 rounded-xl">{{ __('Download PNG') }}</a>
                    <p class="mt-3 break-all text-xs text-base-content/45">{{ $scanUrl }}</p>
                </div>

                <div class="card bg-base-100 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-base-content">{{ __('Booking rules') }}</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-base-content/55">{{ __('Minimum') }}</dt>
                            <dd class="tabular-nums text-base-content">{{ (int) $resource->rule('min_duration_minutes', 15) }} {{ __('min') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-base-content/55">{{ __('Maximum') }}</dt>
                            <dd class="tabular-nums text-base-content">{{ (int) $resource->rule('max_duration_minutes', 480) }} {{ __('min') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-base-content/55">{{ __('Buffer') }}</dt>
                            <dd class="tabular-nums text-base-content">{{ (int) $resource->rule('buffer_minutes', 0) }} {{ __('min') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-base-content/55">{{ __('Advance limit') }}</dt>
                            <dd class="tabular-nums text-base-content">
                                {{ $resource->rule('max_advance_days') ? $resource->rule('max_advance_days').' '.__('days') : __('Default') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
