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
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Standard permissions across all tenants
        $permissions = [
            'manage-team',
            'manage-billing',
            'manage-warehouses',
            'manage-products',
            'manage-stock',
            'manage-orders',
            'view-reports',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }
    }

    /**
     * Helper to seed standard roles for a given team/tenant.
     */
    public static function seedRolesForTeam(int $teamId): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Standard permissions across all tenants
        $permissions = [
            'manage-team',
            'manage-billing',
            'manage-warehouses',
            'manage-products',
            'manage-stock',
            'manage-orders',
            'view-reports',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 1. Owner Role - Has all permissions
        $ownerRole = Role::firstOrCreate([
            'name' => 'owner',
            'guard_name' => 'web',
            'team_id' => $teamId,
        ]);
        $ownerRole->syncPermissions($permissions);

        // 2. Admin Role - Has operational & management permissions (excluding billing/team deletion)
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
            'team_id' => $teamId,
        ]);
        $adminRole->syncPermissions([
            'manage-warehouses',
            'manage-products',
            'manage-stock',
            'manage-orders',
            'view-reports',
        ]);

        // 3. Staff Role - Daily stock operations & orders
        $staffRole = Role::firstOrCreate([
            'name' => 'staff',
            'guard_name' => 'web',
            'team_id' => $teamId,
        ]);
        $staffRole->syncPermissions([
            'manage-stock',
            'manage-orders',
        ]);

        // 4. Viewer Role - Read-only reporting access
        $viewerRole = Role::firstOrCreate([
            'name' => 'viewer',
            'guard_name' => 'web',
            'team_id' => $teamId,
        ]);
        $viewerRole->syncPermissions([
            'view-reports',
        ]);
    }
}
