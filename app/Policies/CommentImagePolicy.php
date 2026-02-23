<?php

namespace App\Policies;

use App\Models\CommentImage;
use App\Models\User;

class CommentImagePolicy
{
    /**
     * Просмотр списка изображений — привязан к праву просмотра комментариев.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Comment.view');
    }

    public function delete(User $user, CommentImage $image): bool
    {
        return $user->isActive() && $user->hasPermissionTo('CommentImage.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->isActive() && $user->hasPermissionTo('CommentImage.delete');
    }
}
