<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $user = auth()->user();

        return [
            'unreadCount' => $user->unreadNotifications()->count(),
        ];
    }
}; ?>

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-base-300/70 bg-base-100/90 shadow-base-content/5 backdrop-blur-xl">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex items-center gap-6">
                <x-brand />

                <div class="hidden items-center gap-1 lg:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</x-nav-link>
                    <x-nav-link :href="route('calendar')" :active="request()->routeIs('calendar')" wire:navigate>{{ __('Calendar') }}</x-nav-link>
                    <x-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')" wire:navigate>{{ __('Bookings') }}</x-nav-link>
                    <x-nav-link :href="route('resources.index')" :active="request()->routeIs('resources.*')" wire:navigate>{{ __('Resources') }}</x-nav-link>

                    @can('approve-bookings')
                        <x-nav-link :href="route('approvals.index')" :active="request()->routeIs('approvals.*')" wire:navigate>{{ __('Approvals') }}</x-nav-link>
                    @endcan

                    @can('view-reports')
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" wire:navigate>{{ __('Reports') }}</x-nav-link>
                    @endcan

                    @can('manage-catalog')
                        <x-nav-group :label="__('Admin')" :active="request()->routeIs('resource-types.*', 'users.*', 'settings')">
                            <x-dropdown-link :href="route('resource-types.index')" :active="request()->routeIs('resource-types.*')" wire:navigate>{{ __('Resource types') }}</x-dropdown-link>
                            @can('manage-users')
                                <x-dropdown-link :href="route('users.index')" :active="request()->routeIs('users.*')" wire:navigate>{{ __('Users') }}</x-dropdown-link>
                            @endcan
                            @can('manage-settings')
                                <x-dropdown-link :href="route('settings')" :active="request()->routeIs('settings')" wire:navigate>{{ __('Settings') }}</x-dropdown-link>
                            @endcan
                        </x-nav-group>
                    @endcan
                </div>
            </div>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="btn btn-ghost btn-circle"
                    aria-label="{{ __('Toggle theme') }}"
                    onclick="(function(){var r=document.documentElement;var c=r.getAttribute('data-theme');var d=c?c==='bookingkom-dark':window.matchMedia('(prefers-color-scheme: dark)').matches;var n=d?'bookingkom':'bookingkom-dark';r.setAttribute('data-theme',n);try{localStorage.setItem('theme',n)}catch(e){}})()"
                >
                    <svg class="h-5 w-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                    <svg class="hidden h-5 w-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.36 6.36l-.71-.71M6.34 6.34l-.71-.71m12.73 0l-.71.71M6.34 17.66l-.71.71M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
                </button>

                <div class="relative" x-data="{ bell: false }" @click.outside="bell = false">
                    <a href="{{ route('notifications.index') }}" wire:navigate class="btn btn-ghost btn-circle relative" aria-label="{{ __('Notifications') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0a3 3 0 1 1-6 0"/></svg>
                        @if ($unreadCount > 0)
                            <span class="absolute -right-0.5 -top-0.5 grid min-w-4 place-items-center rounded-full bg-error px-1 text-[10px] font-bold text-error-content">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>
                </div>

                <div class="hidden sm:block">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="btn btn-ghost h-10 gap-2 rounded-xl px-2.5">
                                <span class="grid size-7 place-items-center rounded-full bg-primary/10 text-xs font-bold text-primary" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                                <span class="max-w-36 truncate" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></span>
                                <svg class="h-4 w-4 opacity-60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-base-200 px-4 py-2">
                                <p class="text-sm font-medium text-base-content">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-base-content/60">{{ auth()->user()->role->label() }}</p>
                            </div>
                            <x-dropdown-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>{{ __('Profile') }}</x-dropdown-link>
                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>{{ __('Log Out') }}</x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                </div>

                <button class="btn btn-ghost btn-square rounded-xl lg:hidden" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="mobile-navigation" aria-label="{{ __('Menu') }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-navigation" x-show="open" x-transition class="border-t border-base-300 bg-base-100 lg:hidden">
        <div class="mx-auto max-w-7xl space-y-1 px-4 py-3 sm:px-6">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('calendar')" :active="request()->routeIs('calendar')" wire:navigate>{{ __('Calendar') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.*')" wire:navigate>{{ __('Bookings') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('resources.index')" :active="request()->routeIs('resources.*')" wire:navigate>{{ __('Resources') }}</x-responsive-nav-link>

            @can('approve-bookings')
                <x-responsive-nav-link :href="route('approvals.index')" :active="request()->routeIs('approvals.*')" wire:navigate>{{ __('Approvals') }}</x-responsive-nav-link>
            @endcan

            @can('view-reports')
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" wire:navigate>{{ __('Reports') }}</x-responsive-nav-link>
            @endcan

            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')" wire:navigate>{{ __('Notifications') }}@if ($unreadCount > 0) <span class="badge badge-error badge-sm ms-1">{{ $unreadCount }}</span>@endif</x-responsive-nav-link>

            @can('manage-catalog')
                <p class="px-3 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-base-content/40">{{ __('Admin') }}</p>
                <x-responsive-nav-link :href="route('resource-types.index')" :active="request()->routeIs('resource-types.*')" wire:navigate>{{ __('Resource types') }}</x-responsive-nav-link>
                @can('manage-users')
                    <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" wire:navigate>{{ __('Users') }}</x-responsive-nav-link>
                @endcan
                @can('manage-settings')
                    <x-responsive-nav-link :href="route('settings')" :active="request()->routeIs('settings')" wire:navigate>{{ __('Settings') }}</x-responsive-nav-link>
                @endcan
            @endcan

            <div class="mt-2 border-t border-base-300 pt-2 sm:hidden">
                <div class="px-3 py-1">
                    <div class="text-base font-medium">{{ auth()->user()->name }}</div>
                    <div class="text-sm text-base-content/60">{{ auth()->user()->email }}</div>
                </div>
                <x-responsive-nav-link :href="route('profile')" :active="request()->routeIs('profile')" wire:navigate>{{ __('Profile') }}</x-responsive-nav-link>
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>{{ __('Log Out') }}</x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
