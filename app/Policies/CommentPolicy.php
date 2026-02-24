<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Comment.view');
    }

    public function view(User $user, Comment $comment): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Comment.view');
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Comment.create');
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Comment.update');
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Comment.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Comment.delete');
    }

    public function viewIp(User $user): bool
    {
        return $user->isActive()
            && $user->hasPermissionTo('Comment.view')
            && $user->hasPermissionTo('Comment.viewIp');
    }

    public function pin(User $user, Comment $comment): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Comment.pin');
    }
}
