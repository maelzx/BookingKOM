<?php

namespace App\Policies;

use App\Models\ResourceType;
use App\Models\User;

class ResourceTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isResourceManager();
    }

    public function update(User $user, ResourceType $resourceType): bool
    {
        return $user->isResourceManager();
    }

    public function delete(User $user, ResourceType $resourceType): bool
    {
        return false;
    }
}
