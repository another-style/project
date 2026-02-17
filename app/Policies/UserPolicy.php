<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('User.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('User.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('User.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('User.update');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('User.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('User.delete');
    }
}
