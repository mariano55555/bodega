<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class VisorWarehouseAccessSeeder extends Seeder
{
    /**
     * Permissions added after the original roles/permissions setup.
     *
     * These gate the features the read-only "visor-warehouse" role must not
     * reach. They are granted to every role EXCEPT visor-warehouse, so only
     * that role is restricted while every other role keeps its current access.
     *
     * @return list<string>
     */
    private function newPermissions(): array
    {
        return [
            // Write permissions for modules the viewer sees but cannot modify
            'products.create', 'products.edit', 'products.delete',
            'categories.create', 'categories.edit', 'categories.delete',
            'donations.create', 'donations.edit', 'donations.delete',
            'dispatches.create', 'dispatches.edit', 'dispatches.delete',
            'transfers.create', 'transfers.edit', 'transfers.delete',
            'internal-productions.create', 'internal-productions.edit', 'internal-productions.delete',
            'adjustments.create', 'adjustments.edit', 'adjustments.delete',
            'closures.create', 'closures.edit', 'closures.delete',

            // Access/view permissions for menus hidden entirely from the viewer
            'dte.access',
            'imports.access',
            'units.view',
            'suppliers.view',
            'donors.view',
            'employees.view',
            'warehouse-management.access',
            'inventory-queries.access',
            'user-management.access',
        ];
    }

    /**
     * Roles that keep full access (everyone except the read-only viewer).
     *
     * @return list<string>
     */
    private function nonViewerRoles(): array
    {
        return [
            'super-admin',
            'company-admin',
            'branch-manager',
            'warehouse-manager',
            'warehouse-operator',
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->newPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();

        // NOTE: the `roles` table has a physical `permissions` column that shadows
        // Spatie's `permissions` relationship on the Eloquent model, so we attach
        // through the relationship method (which populates role_has_permissions)
        // instead of givePermissionTo(), whose `$this->permissions` accessor breaks here.
        foreach ($this->nonViewerRoles() as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role !== null) {
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
