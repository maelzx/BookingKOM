<?php

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Booking $booking;

    public string $decisionNote = '';

    public string $cancelReason = '';

    public function mount(Booking $booking): void
    {
        Gate::authorize('view', $booking);

        $this->booking = $booking;
    }

    public function approve(BookingService $service, ?int $resourceId = null): void
    {
        if ($resourceId) {
            $resource = $this->booking->resources->firstWhere('id', $resourceId);
            abort_unless($resource && Gate::allows('approveResource', [$this->booking, $resource]), 403);
        } else {
            Gate::authorize('approve', $this->booking);
        }

        $service->approve($this->booking, auth()->user(), $resourceId, $this->decisionNote ?: null);
        $this->reset('decisionNote');
        $this->booking->refresh();

        session()->flash('status', __('Booking approved.'));
    }

    public function reject(BookingService $service, ?int $resourceId = null): void
    {
        if ($resourceId) {
            $resource = $this->booking->resources->firstWhere('id', $resourceId);
            abort_unless($resource && Gate::allows('rejectResource', [$this->booking, $resource]), 403);
        } else {
            Gate::authorize('reject', $this->booking);
        }

        $service->reject($this->booking, auth()->user(), $this->decisionNote ?: null, $resourceId);
        $this->reset('decisionNote');
        $this->booking->refresh();

        session()->flash('status', __('Booking rejected.'));
    }

    public function cancel(BookingService $service): void
    {
        Gate::authorize('cancel', $this->booking);

        $service->cancel($this->booking, auth()->user(), $this->cancelReason ?: null);
        $this->booking->refresh();

        session()->flash('status', __('Booking cancelled.'));
    }

    public function complete(BookingService $service): void
    {
        Gate::authorize('complete', $this->booking);

        $service->complete($this->booking, auth()->user());
        $this->booking->refresh();

        session()->flash('status', __('Booking marked completed.'));
    }

    public function markNoShow(BookingService $service): void
    {
        Gate::authorize('markNoShow', $this->booking);

        $service->markNoShow($this->booking, auth()->user());
        $this->booking->refresh();

        session()->flash('status', __('Booking marked as no-show.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $this->booking->load(['resources.type', 'organiser', 'attendees.user', 'approver', 'rejecter', 'occurrences.resources']);

        return [
            'pivotStates' => [
                'not_required' => ['label' => __('Auto-approved'), 'color' => 'bg-gray-100 text-gray-800'],
                'pending' => ['label' => __('Pending'), 'color' => 'bg-amber-100 text-amber-800'],
                'approved' => ['label' => __('Approved'), 'color' => 'bg-green-100 text-green-800'],
                'rejected' => ['label' => __('Rejected'), 'color' => 'bg-red-100 text-red-800'],
            ],
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="$booking->title" :subtitle="$booking->reference">
            <x-slot name="actions">
                <a href="{{ route('bookings.index') }}" wire:navigate class="btn btn-ghost btn-sm rounded-xl">{{ __('Back') }}</a>
                @can('update', $booking)
                    <a href="{{ route('bookings.edit', $booking) }}" wire:navigate class="btn btn-outline btn-sm rounded-xl">{{ __('Edit') }}</a>
                @endcan
            </x-slot>
        </x-page-header>

        @if (session('status'))
            <div role="alert" class="alert alert-success"><span>{{ session('status') }}</span></div>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <div class="card bg-base-100 p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <x-status-badge :status="$booking->status" />
                            <p class="mt-3 text-sm text-base-content/70">{{ $booking->purpose ?: __('No notes provided.') }}</p>
                        </div>
                        <div class="text-right text-sm">
                            <p class="font-semibold text-base-content">{{ $booking->starts_at->format('l, d M Y') }}</p>
                            <p class="tabular-nums text-base-content/70">{{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}</p>
                            <p class="mt-1 text-xs text-base-content/50">{{ trans_choice(':count min|:count min', $booking->durationInMinutes(), ['count' => $booking->durationInMinutes()]) }}</p>
                        </div>
                    </div>

                    <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 border-t border-base-200 pt-5 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Organiser') }}</dt>
                            <dd class="mt-1 text-base-content">{{ $booking->organiser?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Department') }}</dt>
                            <dd class="mt-1 text-base-content">{{ $booking->department ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Created') }}</dt>
                            <dd class="mt-1 text-base-content">{{ $booking->created_at->format('d M Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="card bg-base-100 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-base-content">{{ __('Resources') }}</h2>
                    <ul class="mt-4 divide-y divide-base-200">
                        @foreach ($booking->resources as $resource)
                            @php $state = $pivotStates[$resource->pivot->status] ?? $pivotStates['pending']; @endphp
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('resources.show', $resource) }}" wire:navigate class="text-sm font-medium text-primary hover:opacity-80">{{ $resource->name }}</a>
                                    <p class="text-xs text-base-content/55">{{ $resource->type?->name ?? $resource->code }} · {{ $resource->location ?? '—' }}</p>
                                    @if ($resource->pivot->note)
                                        <p class="mt-1 text-xs text-base-content/60">{{ __('Note: :note', ['note' => $resource->pivot->note]) }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-status-badge :label="$state['label']" :color="$state['color']" />
                                    @if ($resource->pivot->status === 'pending' && Gate::allows('approveResource', [$booking, $resource]))
                                        <button wire:click="approve({{ $resource->id }})" class="btn btn-success btn-xs rounded-lg">{{ __('Approve') }}</button>
                                    @endif
                                    @if ($resource->pivot->status === 'pending' && Gate::allows('rejectResource', [$booking, $resource]))
                                        <button wire:click="reject({{ $resource->id }})" wire:confirm="{{ __('Reject this booking?') }}" class="btn btn-error btn-outline btn-xs rounded-lg">{{ __('Reject') }}</button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if ($booking->attendees->isNotEmpty())
                    <div class="card bg-base-100 p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-base-content">{{ __('Attendees') }}</h2>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($booking->attendees as $attendee)
                                <li class="inline-flex items-center gap-2 rounded-full bg-base-200 px-3 py-1 text-sm">
                                    <span class="font-medium text-base-content">{{ $attendee->name }}</span>
                                    @if ($attendee->is_organiser)
                                        <span class="badge badge-primary badge-sm">{{ __('Organiser') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($booking->occurrences->isNotEmpty())
                    <div class="card bg-base-100 p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-base-content">{{ __('Recurring occurrences') }}</h2>
                        <ul class="mt-4 divide-y divide-base-200">
                            @foreach ($booking->occurrences as $occurrence)
                                <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                                    <a href="{{ route('bookings.show', $occurrence) }}" wire:navigate class="text-primary hover:opacity-80">{{ $occurrence->starts_at->format('D, d M Y H:i') }}</a>
                                    <x-status-badge :status="$occurrence->status" />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="space-y-5">
                @if ($booking->status->isCancellable() || $booking->status === \App\Enums\BookingStatus::Confirmed)
                    <div class="card space-y-4 bg-base-100 p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-base-content">{{ __('Actions') }}</h2>

                        @if ($booking->status === \App\Enums\BookingStatus::Pending && (Gate::allows('approve', $booking) || Gate::allows('reject', $booking)))
                            <div>
                                <x-input-label for="decisionNote" :value="__('Decision note (optional)')" />
                                <textarea wire:model="decisionNote" id="decisionNote" rows="2" class="textarea textarea-bordered mt-1 block w-full rounded-md border-base-300 focus:border-primary focus:ring-primary"></textarea>
                            </div>
                            <div class="flex gap-2">
                                @if (Gate::allows('approve', $booking))
                                    <button wire:click="approve" class="btn btn-success btn-sm flex-1 rounded-xl">{{ __('Approve all') }}</button>
                                @endif
                                @if (Gate::allows('reject', $booking))
                                    <button wire:click="reject" wire:confirm="{{ __('Reject this booking?') }}" class="btn btn-error btn-sm flex-1 rounded-xl">{{ __('Reject') }}</button>
                                @endif
                            </div>
                        @endif

                        @if (Gate::allows('complete', $booking) && in_array($booking->status, [\App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::Approved], true))
                            <div class="flex gap-2">
                                <button wire:click="complete" class="btn btn-outline btn-sm flex-1 rounded-xl">{{ __('Mark completed') }}</button>
                                <button wire:click="markNoShow" wire:confirm="{{ __('Mark as no-show?') }}" class="btn btn-ghost btn-sm flex-1 rounded-xl">{{ __('No-show') }}</button>
                            </div>
                        @endif

                        @can('cancel', $booking)
                            <div class="border-t border-base-200 pt-4">
                                <x-input-label for="cancelReason" :value="__('Cancellation reason (optional)')" />
                                <textarea wire:model="cancelReason" id="cancelReason" rows="2" class="textarea textarea-bordered mt-1 block w-full rounded-md border-base-300 focus:border-primary focus:ring-primary"></textarea>
                                <button wire:click="cancel" wire:confirm="{{ __('Cancel this booking?') }}" class="btn btn-error btn-outline btn-sm mt-3 w-full rounded-xl">{{ __('Cancel booking') }}</button>
                            </div>
                        @endcan
                    </div>
                @endif

                @if ($booking->approved_at || $booking->rejected_at || $booking->cancelled_at)
                    <div class="card bg-base-100 p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-base-content">{{ __('Decision history') }}</h2>
                        <ul class="mt-3 space-y-3 text-sm">
                            @if ($booking->approved_at)
                                <li>
                                    <p class="text-base-content">{{ __('Approved by :name', ['name' => $booking->approver?->name ?? '—']) }}</p>
                                    <p class="text-xs text-base-content/55">{{ $booking->approved_at->format('d M Y H:i') }}</p>
                                </li>
                            @endif
                            @if ($booking->rejected_at)
                                <li>
                                    <p class="text-base-content">{{ __('Rejected by :name', ['name' => $booking->rejecter?->name ?? '—']) }}</p>
                                    <p class="text-xs text-base-content/55">{{ $booking->rejected_at->format('d M Y H:i') }}</p>
                                </li>
                            @endif
                            @if ($booking->decision_note)
                                <li>
                                    <p class="text-xs uppercase tracking-wide text-base-content/45">{{ __('Note') }}</p>
                                    <p class="text-base-content">{{ $booking->decision_note }}</p>
                                </li>
                            @endif
                            @if ($booking->cancelled_at)
                                <li>
                                    <p class="text-base-content">{{ __('Cancelled :time', ['time' => $booking->cancelled_at->format('d M Y H:i')]) }}</p>
                                    @if ($booking->cancel_reason)
                                        <p class="text-xs text-base-content/55">{{ $booking->cancel_reason }}</p>
                                    @endif
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
