<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'user';

    public string $password = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        Gate::authorize('manage-users');

        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->password = '';
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'email', 'role', 'password']);
        $this->role = Role::User->value;
    }

    public function save(): void
    {
        Gate::authorize('manage-users');

        $editing = $this->editingId ? User::findOrFail($this->editingId) : null;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($editing?->id)],
            'role' => ['required', Rule::in(Role::values())],
            'password' => [$editing ? 'nullable' : 'required', 'string', Password::min(8)],
        ]);

        if (blank($validated['password'])) {
            unset($validated['password']);
        }

        if ($editing) {
            $editing->update($validated);
        } else {
            $user = User::create($validated);
            $user->email_verified_at = now();
            $user->save();
        }

        $this->cancel();
        session()->flash('status', __('User saved.'));
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-users');

        abort_if($id === auth()->id(), 403);

        User::findOrFail($id)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'users' => User::query()
                ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                    $term = '%'.$this->search.'%';
                    $query->where('name', 'like', $term)->orWhere('email', 'like', $term);
                }))
                ->orderBy('name')
                ->paginate(15),
            'roles' => Role::cases(),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Users')" :subtitle="__('Manage access and roles.')" />

        @if (session('status'))
            <div role="alert" class="alert alert-success"><span>{{ session('status') }}</span></div>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="card bg-base-100 p-5 sm:p-6">
                <h2 class="text-base font-semibold text-base-content">{{ $editingId ? __('Edit user') : __('New user') }}</h2>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <div>
                        <x-input-label for="user-name" :value="__('Name')" />
                        <x-text-input wire:model="name" id="user-name" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="user-email" :value="__('Email')" />
                        <x-text-input wire:model="email" id="user-email" type="email" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="user-role" :value="__('Role')" />
                        <select wire:model="role" id="user-role" class="select select-bordered mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary">
                            @foreach ($roles as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="user-password" :value="$editingId ? __('New password (optional)') : __('Password')" />
                        <x-text-input wire:model="password" id="user-password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>{{ $editingId ? __('Update') : __('Add user') }}</x-primary-button>
                        @if ($editingId)
                            <button type="button" wire:click="cancel" class="text-sm text-base-content/70 hover:text-base-content">{{ __('Cancel') }}</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2">
                <div class="card bg-base-100 p-4">
                    <x-text-input wire:model.live.debounce.300ms="search" type="search" class="block w-full" placeholder="{{ __('Search users…') }}" />
                </div>

                <div class="mt-4 card overflow-hidden bg-base-100">
                    <table class="table table-sm">
                        <thead class="bg-base-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60">{{ __('Role') }}</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-300">
                            @forelse ($users as $user)
                                <tr wire:key="user-{{ $user->id }}">
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-base-content">{{ $user->name }}</p>
                                        <p class="text-xs text-base-content/50">{{ $user->email }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-base-content/70">{{ $user->role->label() }}</td>
                                    <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                        <button wire:click="edit({{ $user->id }})" class="text-base-content/70 hover:text-base-content">{{ __('Edit') }}</button>
                                        @if ($user->id !== auth()->id())
                                            <button wire:click="delete({{ $user->id }})" wire:confirm="{{ __('Delete this user?') }}" class="ms-3 text-error hover:opacity-80">{{ __('Delete') }}</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-base-content/60">{{ __('No users found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="mt-4">{{ $users->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
