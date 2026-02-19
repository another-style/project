<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Tag.view');
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Tag.view');
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Tag.create');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Tag.update');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Tag.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Tag.delete');
    }
}
