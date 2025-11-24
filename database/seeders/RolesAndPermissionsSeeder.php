<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// use Spatie\Permission\Models\Permission;
// use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for branches
        $branchPermissions = [
            'view-branches',
            'create-branches',
            'edit-branches',
            'delete-branches',
            'manage-branch-status',
            'filter-branches-by-company',
        ];

        // Create permissions for warehouses
        $warehousePermissions = [
            'view-warehouses',
            'create-warehouses',
            'edit-warehouses',
            'delete-warehouses',
            'manage-warehouse-status',
            'view-warehouse-capacity',
            'filter-warehouses-by-company',
            'filter-warehouses-by-branch',
        ];

        // Create permissions for companies
        $companyPermissions = [
            'view-companies',
            'create-companies',
            'edit-companies',
            'delete-companies',
            'manage-company-users',
        ];

        // Create permissions for inventory
        $inventoryPermissions = [
            'view-inventory',
            'create-inventory',
            'edit-inventory',
            'delete-inventory',
            'transfer-inventory',
            'view-inventory-reports',
        ];

        // Create permissions for users
        $userPermissions = [
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'assign-roles',
            'manage-user-permissions',
        ];

        // Combine all permissions
        $allPermissions = array_merge(
            $branchPermissions,
            $warehousePermissions,
            $companyPermissions,
            $inventoryPermissions,
            $userPermissions
        );

        // Create permissions
        foreach ($allPermissions as $permission) {
            \App\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - Full access to everything
        $superAdmin = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super-admin',
        ], [
            'slug' => 'super-admin',
            'active_at' => now(),
        ]);
        $superAdmin->syncPermissions($allPermissions);

        // Company Admin - Full access to their company data
        $companyAdmin = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'company-admin',
        ], [
            'slug' => 'company-admin',
            'active_at' => now(),
        ]);
        $companyAdmin->syncPermissions([
            // Branch permissions
            'view-branches',
            'create-branches',
            'edit-branches',
            'delete-branches',
            'manage-branch-status',
            'filter-branches-by-company',

            // Warehouse permissions
            'view-warehouses',
            'create-warehouses',
            'edit-warehouses',
            'delete-warehouses',
            'manage-warehouse-status',
            'view-warehouse-capacity',
            'filter-warehouses-by-company',
            'filter-warehouses-by-branch',

            // Company permissions (limited)
            'view-companies',
            'edit-companies',

            // Inventory permissions
            'view-inventory',
            'create-inventory',
            'edit-inventory',
            'delete-inventory',
            'transfer-inventory',
            'view-inventory-reports',

            // User permissions (limited)
            'view-users',
            'create-users',
            'edit-users',
            'assign-roles',
        ]);

        // Branch Manager - Access to assigned branches and their warehouses
        $branchManager = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'branch-manager',
        ], [
            'slug' => 'branch-manager',
            'active_at' => now(),
        ]);
        $branchManager->syncPermissions([
            // Branch permissions (limited)
            'view-branches',
            'edit-branches',
            'manage-branch-status',

            // Warehouse permissions
            'view-warehouses',
            'create-warehouses',
            'edit-warehouses',
            'manage-warehouse-status',
            'view-warehouse-capacity',
            'filter-warehouses-by-branch',

            // Inventory permissions
            'view-inventory',
            'create-inventory',
            'edit-inventory',
            'transfer-inventory',
            'view-inventory-reports',

            // Limited user permissions
            'view-users',
        ]);

        // Warehouse Manager - Access to assigned warehouses
        $warehouseManager = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'warehouse-manager',
        ], [
            'slug' => 'warehouse-manager',
            'active_at' => now(),
        ]);
        $warehouseManager->syncPermissions([
            // Limited branch permissions
            'view-branches',

            // Warehouse permissions
            'view-warehouses',
            'edit-warehouses',
            'manage-warehouse-status',
            'view-warehouse-capacity',

            // Full inventory permissions
            'view-inventory',
            'create-inventory',
            'edit-inventory',
            'transfer-inventory',
            'view-inventory-reports',

            // Limited user permissions
            'view-users',
        ]);

        // Warehouse Operator - Read-only access to assigned warehouses
        $warehouseOperator = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'warehouse-operator',
        ], [
            'slug' => 'warehouse-operator',
            'active_at' => now(),
        ]);
        $warehouseOperator->syncPermissions([
            // Read-only permissions
            'view-branches',
            'view-warehouses',
            'view-warehouse-capacity',
            'view-inventory',
            'view-inventory-reports',
            'view-users',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created roles: super-admin, company-admin, branch-manager, warehouse-manager, warehouse-operator');
        $this->command->info('Created '.count($allPermissions).' permissions');
    }
}
