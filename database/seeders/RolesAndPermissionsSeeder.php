<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Сброс кэша ролей и прав
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

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
    }
}
