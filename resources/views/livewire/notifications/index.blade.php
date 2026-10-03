<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()->whereKey($id)->first()?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'notifications' => auth()->user()->notifications()->latest()->paginate(20),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Notifications')" :subtitle="__('Booking activity and decisions.')">
            <x-slot name="actions">
                @if ($unreadCount > 0)
                    <button wire:click="markAllAsRead" class="btn btn-outline btn-sm rounded-xl">{{ __('Mark all read') }}</button>
                @endif
            </x-slot>
        </x-page-header>

        <div class="card divide-y divide-base-200 bg-base-100">
            @forelse ($notifications as $notification)
                @php $data = $notification->data; @endphp
                <div class="flex items-start gap-4 p-4 sm:p-5 {{ $notification->read_at ? '' : 'bg-primary/5' }}" wire:key="notification-{{ $notification->id }}">
                    <span class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-xl {{ $notification->read_at ? 'bg-base-200 text-base-content/50' : 'bg-primary/10 text-primary' }}">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-base-content">{{ $data['title'] ?? __('Notification') }}</p>
                        <p class="mt-0.5 text-xs text-base-content/60">
                            {{ $data['reference'] ?? '' }}
                            @if (! empty($data['starts_at']))
                                · {{ \Illuminate\Support\Carbon::parse($data['starts_at'])->format('d M Y H:i') }}
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-base-content/45">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if (! empty($data['url']))
                            <a href="{{ $data['url'] }}" wire:navigate class="btn btn-ghost btn-xs rounded-lg">{{ __('Open') }}</a>
                        @endif
                        @if (! $notification->read_at)
                            <button wire:click="markAsRead('{{ $notification->id }}')" class="text-xs text-primary hover:opacity-80">{{ __('Mark read') }}</button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-sm text-base-content/60">{{ __('No notifications yet.') }}</div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div>{{ $notifications->links() }}</div>
        @endif
    </div>
</div>
