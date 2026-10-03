<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;

class ResourcePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Resource $resource): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isResourceManager();
    }

    public function update(User $user, Resource $resource): bool
    {
        return $user->isResourceManager() && $resource->manager_id === $user->id;
    }

    /**
     * Only administrators may delete resources (Gate::before grants admins).
     */
    public function delete(User $user, Resource $resource): bool
    {
        return false;
    }

    public function manageBlockedPeriods(User $user, Resource $resource): bool
    {
        return $this->update($user, $resource);
    }

    public function viewQrCode(User $user, Resource $resource): bool
    {
        return true;
    }
}
