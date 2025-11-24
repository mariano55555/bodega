<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\seed;

beforeEach(function () {
    seed([\Database\Seeders\RolesAndPermissionsSeeder::class]);
});

describe('Role Model Basic Operations', function () {
    it('can create roles using factory', function () {
        $role = Role::factory()->create(['name' => 'test-role']);

        expect($role->name)->toBe('test-role')
            ->and($role->guard_name)->toBe('web')
            ->and($role->exists)->toBeTrue();
    });

    it('enforces unique role names per guard', function () {
        Role::factory()->create(['name' => 'duplicate-role']);

        expect(fn () => Role::factory()->create(['name' => 'duplicate-role']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('allows same role name with different guards', function () {
        $webRole = Role::factory()->create(['name' => 'admin', 'guard_name' => 'web']);
        $apiRole = Role::factory()->create(['name' => 'admin', 'guard_name' => 'api']);

        expect($webRole->name)->toBe($apiRole->name)
            ->and($webRole->guard_name)->not->toBe($apiRole->guard_name);
    });
});

describe('Role Factory States', function () {
    it('creates super admin role with correct name', function () {
        $role = Role::factory()->superAdmin()->create();

        expect($role->name)->toBe('super-admin')
            ->and($role->guard_name)->toBe('web');
    });

    it('creates company admin role with correct name', function () {
        $role = Role::factory()->companyAdmin()->create();

        expect($role->name)->toBe('company-admin');
    });

    it('creates branch manager role with correct name', function () {
        $role = Role::factory()->branchManager()->create();

        expect($role->name)->toBe('branch-manager');
    });

    it('creates warehouse manager role with correct name', function () {
        $role = Role::factory()->warehouseManager()->create();

        expect($role->name)->toBe('warehouse-manager');
    });

    it('creates warehouse operator role with correct name', function () {
        $role = Role::factory()->warehouseOperator()->create();

        expect($role->name)->toBe('warehouse-operator');
    });
});

describe('Role Permission Assignment', function () {
    it('can assign permissions to roles', function () {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();

        $role->givePermissionTo($permission);

        expect($role->hasPermissionTo($permission))->toBeTrue();
    });

    it('can assign multiple permissions at once', function () {
        $role = Role::factory()->create();
        $permissions = Permission::factory()->count(3)->create();

        $role->givePermissionTo($permissions->pluck('name')->toArray());

        expect($role->permissions)->toHaveCount(3);
        $permissions->each(fn ($permission) => expect($role->hasPermissionTo($permission))->toBeTrue());
    });

    it('can revoke permissions from roles', function () {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();

        $role->givePermissionTo($permission);
        expect($role->hasPermissionTo($permission))->toBeTrue();

        $role->revokePermissionTo($permission);
        expect($role->hasPermissionTo($permission))->toBeFalse();
    });

    it('can sync permissions replacing existing ones', function () {
        $role = Role::factory()->create();
        $initialPermissions = Permission::factory()->count(2)->create();
        $newPermissions = Permission::factory()->count(2)->create();

        $role->syncPermissions($initialPermissions->pluck('name')->toArray());
        expect($role->permissions)->toHaveCount(2);

        $role->syncPermissions($newPermissions->pluck('name')->toArray());
        expect($role->permissions)->toHaveCount(2);

        $newPermissions->each(fn ($permission) => expect($role->hasPermissionTo($permission))->toBeTrue());
        $initialPermissions->each(fn ($permission) => expect($role->hasPermissionTo($permission))->toBeFalse());
    });
});

describe('Role Factory Permission Methods', function () {
    it('creates role with company permissions', function () {
        $role = Role::factory()->withCompanyPermissions()->create();

        $expectedPermissions = [
            'view-companies', 'create-companies', 'edit-companies',
            'delete-companies', 'manage-company-users',
        ];

        foreach ($expectedPermissions as $permission) {
            expect($role->hasPermissionTo($permission))->toBeTrue();
        }
    });

    it('creates role with branch permissions', function () {
        $role = Role::factory()->withBranchPermissions()->create();

        $expectedPermissions = [
            'view-branches', 'create-branches', 'edit-branches',
            'delete-branches', 'manage-branch-status', 'filter-branches-by-company',
        ];

        foreach ($expectedPermissions as $permission) {
            expect($role->hasPermissionTo($permission))->toBeTrue();
        }
    });

    it('creates role with warehouse permissions', function () {
        $role = Role::factory()->withWarehousePermissions()->create();

        $expectedPermissions = [
            'view-warehouses', 'create-warehouses', 'edit-warehouses',
            'delete-warehouses', 'manage-warehouse-status', 'view-warehouse-capacity',
            'filter-warehouses-by-company', 'filter-warehouses-by-branch',
        ];

        foreach ($expectedPermissions as $permission) {
            expect($role->hasPermissionTo($permission))->toBeTrue();
        }
    });

    it('creates role with inventory permissions', function () {
        $role = Role::factory()->withInventoryPermissions()->create();

        $expectedPermissions = [
            'view-inventory', 'create-inventory', 'edit-inventory',
            'delete-inventory', 'transfer-inventory', 'view-inventory-reports',
        ];

        foreach ($expectedPermissions as $permission) {
            expect($role->hasPermissionTo($permission))->toBeTrue();
        }
    });

    it('creates read-only role with view permissions', function () {
        $role = Role::factory()->readOnly()->create();

        $expectedPermissions = [
            'view-branches', 'view-warehouses', 'view-warehouse-capacity',
            'view-inventory', 'view-inventory-reports', 'view-users',
        ];

        foreach ($expectedPermissions as $permission) {
            expect($role->hasPermissionTo($permission))->toBeTrue();
        }

        // Should not have create/edit/delete permissions
        $prohibitedPermissions = [
            'create-branches', 'edit-branches', 'delete-branches',
            'create-warehouses', 'edit-warehouses', 'delete-warehouses',
        ];

        foreach ($prohibitedPermissions as $permission) {
            expect($role->hasPermissionTo($permission))->toBeFalse();
        }
    });

    it('creates role with specific custom permissions', function () {
        $customPermissions = ['custom-permission-1', 'custom-permission-2'];

        // Create the permissions first
        foreach ($customPermissions as $permissionName) {
            Permission::factory()->withName($permissionName)->create();
        }

        $role = Role::factory()->withPermissions($customPermissions)->create();

        foreach ($customPermissions as $permission) {
            expect($role->hasPermissionTo($permission))->toBeTrue();
        }
    });
});

describe('Role User Relationships', function () {
    it('can assign users to roles', function () {
        $role = Role::factory()->create();
        $user = User::factory()->create();

        $user->assignRole($role);

        expect($user->hasRole($role))->toBeTrue()
            ->and($role->users)->toHaveCount(1);
    });

    it('can have multiple users assigned to same role', function () {
        $role = Role::factory()->create();
        $users = User::factory()->count(3)->create();

        $users->each(fn ($user) => $user->assignRole($role));

        expect($role->users)->toHaveCount(3);
        $users->each(fn ($user) => expect($user->hasRole($role))->toBeTrue());
    });

    it('removes role assignments when role is deleted', function () {
        $role = Role::factory()->create();
        $user = User::factory()->create();

        $user->assignRole($role);
        expect($user->hasRole($role))->toBeTrue();

        $role->delete();
        $user->refresh();

        expect($user->roles)->toHaveCount(0);
    });
});

describe('Seeded Roles Validation', function () {
    it('has all required system roles created by seeder', function () {
        $requiredRoles = [
            'super-admin',
            'company-admin',
            'branch-manager',
            'warehouse-manager',
            'warehouse-operator',
        ];

        foreach ($requiredRoles as $roleName) {
            expect(Role::where('name', $roleName)->exists())->toBeTrue();
        }
    });

    it('super admin role has all permissions', function () {
        $superAdmin = Role::where('name', 'super-admin')->first();
        $allPermissions = Permission::all();

        expect($superAdmin->permissions)->toHaveCount($allPermissions->count());
    });

    it('warehouse operator has only view permissions', function () {
        $operator = Role::where('name', 'warehouse-operator')->first();

        $viewPermissions = [
            'view-branches', 'view-warehouses', 'view-warehouse-capacity',
            'view-inventory', 'view-inventory-reports', 'view-users',
        ];

        expect($operator->permissions)->toHaveCount(count($viewPermissions));

        foreach ($viewPermissions as $permission) {
            expect($operator->hasPermissionTo($permission))->toBeTrue();
        }

        // Should not have create/edit/delete permissions
        expect($operator->hasPermissionTo('create-branches'))->toBeFalse()
            ->and($operator->hasPermissionTo('edit-inventory'))->toBeFalse()
            ->and($operator->hasPermissionTo('delete-warehouses'))->toBeFalse();
    });
});

describe('Role Validation Edge Cases', function () {
    it('handles empty permission assignment gracefully', function () {
        $role = Role::factory()->create();

        $role->syncPermissions([]);

        expect($role->permissions)->toHaveCount(0);
    });

    it('prevents duplicate permission assignment', function () {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();

        $role->givePermissionTo($permission);
        $role->givePermissionTo($permission); // Should not create duplicate

        expect($role->permissions)->toHaveCount(1);
    });

    it('handles non-existent permission assignment gracefully', function () {
        $role = Role::factory()->create();

        expect(fn () => $role->givePermissionTo('non-existent-permission'))
            ->toThrow(\Spatie\Permission\Exceptions\PermissionDoesNotExist::class);
    });
});
