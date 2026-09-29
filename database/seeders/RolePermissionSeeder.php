<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view shop', 'place orders', 'manage own orders',
            'process orders', 'manage inventory', 'assign riders',
            'view deliveries', 'update deliveries',
            'manage users', 'manage settings', 'view reports',
        ];

        $newPermissions = [];
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);

            if ($permission->wasRecentlyCreated) {
                $newPermissions[] = $permission;
            }
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        if ($admin->wasRecentlyCreated) {
            $admin->syncPermissions(Permission::all());
        } elseif ($newPermissions) {
            $admin->givePermissionTo($newPermissions);
        }

        $staff = Role::firstOrCreate(['name' => 'staff']);
        if ($staff->wasRecentlyCreated) {
            $staff->syncPermissions(['process orders', 'manage inventory', 'assign riders', 'view deliveries', 'view reports']);
        }

        $rider = Role::firstOrCreate(['name' => 'rider']);
        if ($rider->wasRecentlyCreated) {
            $rider->syncPermissions(['view deliveries', 'update deliveries']);
        }

        $customer = Role::firstOrCreate(['name' => 'customer']);
        if ($customer->wasRecentlyCreated) {
            $customer->syncPermissions(['view shop', 'place orders', 'manage own orders']);
        }
    }
}
