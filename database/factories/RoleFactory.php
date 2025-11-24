<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Spatie\Permission\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().'-role',
            'guard_name' => 'web',
        ];
    }

    /**
     * Create a super admin role.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'super-admin',
        ]);
    }

    /**
     * Create a company admin role.
     */
    public function companyAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'company-admin',
        ]);
    }

    /**
     * Create a branch manager role.
     */
    public function branchManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'branch-manager',
        ]);
    }

    /**
     * Create a warehouse manager role.
     */
    public function warehouseManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'warehouse-manager',
        ]);
    }

    /**
     * Create a warehouse operator role.
     */
    public function warehouseOperator(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'warehouse-operator',
        ]);
    }

    /**
     * Create with specific permissions.
     */
    public function withPermissions(array $permissions): static
    {
        return $this->afterCreating(function (Role $role) use ($permissions) {
            $role->givePermissionTo($permissions);
        });
    }

    /**
     * Create with full company permissions.
     */
    public function withCompanyPermissions(): static
    {
        return $this->afterCreating(function (Role $role) {
            $permissions = [
                'view-companies', 'create-companies', 'edit-companies',
                'delete-companies', 'manage-company-users',
            ];
            $role->givePermissionTo($permissions);
        });
    }

    /**
     * Create with branch permissions.
     */
    public function withBranchPermissions(): static
    {
        return $this->afterCreating(function (Role $role) {
            $permissions = [
                'view-branches', 'create-branches', 'edit-branches',
                'delete-branches', 'manage-branch-status', 'filter-branches-by-company',
            ];
            $role->givePermissionTo($permissions);
        });
    }

    /**
     * Create with warehouse permissions.
     */
    public function withWarehousePermissions(): static
    {
        return $this->afterCreating(function (Role $role) {
            $permissions = [
                'view-warehouses', 'create-warehouses', 'edit-warehouses',
                'delete-warehouses', 'manage-warehouse-status', 'view-warehouse-capacity',
                'filter-warehouses-by-company', 'filter-warehouses-by-branch',
            ];
            $role->givePermissionTo($permissions);
        });
    }

    /**
     * Create with inventory permissions.
     */
    public function withInventoryPermissions(): static
    {
        return $this->afterCreating(function (Role $role) {
            $permissions = [
                'view-inventory', 'create-inventory', 'edit-inventory',
                'delete-inventory', 'transfer-inventory', 'view-inventory-reports',
            ];
            $role->givePermissionTo($permissions);
        });
    }

    /**
     * Create with read-only permissions.
     */
    public function readOnly(): static
    {
        return $this->afterCreating(function (Role $role) {
            $permissions = [
                'view-branches', 'view-warehouses', 'view-warehouse-capacity',
                'view-inventory', 'view-inventory-reports', 'view-users',
            ];
            $role->givePermissionTo($permissions);
        });
    }
}
