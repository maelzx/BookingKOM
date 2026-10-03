<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\Resource;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($booking->user_id === $user->id) {
            return true;
        }

        if ($booking->attendees()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $this->managesAnyResource($user, $booking);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $booking->isEditable() && $booking->canBeManagedBy($user);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $booking->status->isCancellable() && $booking->canBeManagedBy($user);
    }

    public function delete(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Whether the user may approve the booking (or a specific resource line).
     */
    public function approve(User $user, Booking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->managesAnyResource($user, $booking);
    }

    public function complete(User $user, Booking $booking): bool
    {
        return $booking->canBeManagedBy($user)
            || $this->managesAnyResource($user, $booking);
    }

    public function markNoShow(User $user, Booking $booking): bool
    {
        return $this->complete($user, $booking);
    }

    protected function managesAnyResource(User $user, Booking $booking): bool
    {
        return $booking->resources()
            ->where('manager_id', $user->id)
            ->exists();
    }

    /**
     * Approve a specific resource line: only that resource's manager.
     */
    public function approveResource(User $user, Booking $booking, Resource $resource): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $resource->manager_id === $user->id;
    }
}
