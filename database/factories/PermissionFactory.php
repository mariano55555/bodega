<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Permission;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Spatie\Permission\Models\Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2).'-permission',
            'guard_name' => 'web',
        ];
    }

    /**
     * Create company permissions.
     */
    public function companyPermissions(): static
    {
        return $this->sequence(
            ['name' => 'view-companies'],
            ['name' => 'create-companies'],
            ['name' => 'edit-companies'],
            ['name' => 'delete-companies'],
            ['name' => 'manage-company-users']
        );
    }

    /**
     * Create branch permissions.
     */
    public function branchPermissions(): static
    {
        return $this->sequence(
            ['name' => 'view-branches'],
            ['name' => 'create-branches'],
            ['name' => 'edit-branches'],
            ['name' => 'delete-branches'],
            ['name' => 'manage-branch-status'],
            ['name' => 'filter-branches-by-company']
        );
    }

    /**
     * Create warehouse permissions.
     */
    public function warehousePermissions(): static
    {
        return $this->sequence(
            ['name' => 'view-warehouses'],
            ['name' => 'create-warehouses'],
            ['name' => 'edit-warehouses'],
            ['name' => 'delete-warehouses'],
            ['name' => 'manage-warehouse-status'],
            ['name' => 'view-warehouse-capacity'],
            ['name' => 'filter-warehouses-by-company'],
            ['name' => 'filter-warehouses-by-branch']
        );
    }

    /**
     * Create inventory permissions.
     */
    public function inventoryPermissions(): static
    {
        return $this->sequence(
            ['name' => 'view-inventory'],
            ['name' => 'create-inventory'],
            ['name' => 'edit-inventory'],
            ['name' => 'delete-inventory'],
            ['name' => 'transfer-inventory'],
            ['name' => 'view-inventory-reports']
        );
    }

    /**
     * Create user permissions.
     */
    public function userPermissions(): static
    {
        return $this->sequence(
            ['name' => 'view-users'],
            ['name' => 'create-users'],
            ['name' => 'edit-users'],
            ['name' => 'delete-users'],
            ['name' => 'assign-roles'],
            ['name' => 'manage-user-permissions']
        );
    }

    /**
     * Create view-only permissions.
     */
    public function viewOnly(): static
    {
        return $this->sequence(
            ['name' => 'view-branches'],
            ['name' => 'view-warehouses'],
            ['name' => 'view-warehouse-capacity'],
            ['name' => 'view-inventory'],
            ['name' => 'view-inventory-reports'],
            ['name' => 'view-users']
        );
    }

    /**
     * Create administrative permissions.
     */
    public function administrative(): static
    {
        return $this->sequence(
            ['name' => 'create-companies'],
            ['name' => 'delete-companies'],
            ['name' => 'manage-company-users'],
            ['name' => 'assign-roles'],
            ['name' => 'manage-user-permissions']
        );
    }

    /**
     * Create with specific permission name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Create with specific guard.
     */
    public function withGuard(string $guard): static
    {
        return $this->state(fn (array $attributes) => [
            'guard_name' => $guard,
        ]);
    }
}
