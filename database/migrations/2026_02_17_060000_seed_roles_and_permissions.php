<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'AdminPanel.access',
            'Role.view',
            'Role.create',
            'Role.update',
            'Role.delete',
            'Permission.view',
            'Permission.create',
            'Permission.update',
            'Permission.delete',
            'User.view',
            'User.create',
            'User.update',
            'User.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo($permissions);

        $user = \App\Models\User::where('email', 'test@example.com')->first();
        if ($user) {
            $user->assignRole($admin);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = Role::findByName('admin');
        if ($role) {
            $role->delete();
        }

        $permissions = [
            'AdminPanel.access',
            'Role.view',
            'Role.create',
            'Role.update',
            'Role.delete',
            'Permission.view',
            'Permission.create',
            'Permission.update',
            'Permission.delete',
            'User.view',
            'User.create',
            'User.update',
            'User.delete',
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::findByName($permission);
            if ($perm) {
                $perm->delete();
            }
        }
    }
};
