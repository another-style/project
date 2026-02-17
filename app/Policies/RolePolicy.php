<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Role.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('Role.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Role.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('Role.update');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('Role.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('Role.delete');
    }
}
