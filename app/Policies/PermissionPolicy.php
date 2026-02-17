<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Permission.view');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Permission.view');
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Permission.create');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Permission.update');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Permission.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->isActive() && $user->hasPermissionTo('Permission.delete');
    }
}
