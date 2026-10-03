<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'notify_booking_created',
    'notify_approval_required',
    'notify_booking_decisions',
    'notify_booking_reminders',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'notify_booking_created' => 'boolean',
            'notify_approval_required' => 'boolean',
            'notify_booking_decisions' => 'boolean',
            'notify_booking_reminders' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isResourceManager(): bool
    {
        return $this->role === Role::ResourceManager;
    }

    public function isUser(): bool
    {
        return $this->role === Role::User;
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Resources this user owns or manages.
     *
     * @return HasMany<resource, $this>
     */
    public function managedResources(): HasMany
    {
        return $this->hasMany(Resource::class, 'manager_id');
    }

    /**
     * @return HasMany<BookingAttendee, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(BookingAttendee::class);
    }
}
