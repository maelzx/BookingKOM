<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Resource;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * Admins (via Gate::before), the uploader, or the manager of the resource
     * the file is attached to may read a private attachment.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        if ($attachment->uploaded_by === $user->id) {
            return true;
        }

        $parent = $attachment->relationLoaded('attachable')
            ? $attachment->attachable
            : $attachment->attachable()->first();

        return $parent instanceof Resource && $parent->manager_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if ($attachment->uploaded_by === $user->id) {
            return true;
        }

        $parent = $attachment->relationLoaded('attachable')
            ? $attachment->attachable
            : $attachment->attachable()->first();

        return $parent instanceof Resource && $parent->manager_id === $user->id;
    }
}
