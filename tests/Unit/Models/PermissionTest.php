<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\seed;

beforeEach(function () {
    seed([\Database\Seeders\RolesAndPermissionsSeeder::class]);
});

describe('Permission Model Basic Operations', function () {
    it('can create permissions using factory', function () {
        $permission = Permission::factory()->create(['name' => 'test-permission']);

        expect($permission->name)->toBe('test-permission')
            ->and($permission->guard_name)->toBe('web')
            ->and($permission->exists)->toBeTrue();
    });

    it('enforces unique permission names per guard', function () {
        Permission::factory()->create(['name' => 'duplicate-permission']);

        expect(fn () => Permission::factory()->create(['name' => 'duplicate-permission']))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('allows same permission name with different guards', function () {
        $webPermission = Permission::factory()->create(['name' => 'admin', 'guard_name' => 'web']);
        $apiPermission = Permission::factory()->create(['name' => 'admin', 'guard_name' => 'api']);

        expect($webPermission->name)->toBe($apiPermission->name)
            ->and($webPermission->guard_name)->not->toBe($apiPermission->guard_name);
    });
});

describe('Permission Factory States', function () {
    it('creates company permissions using factory state', function () {
        $permissions = Permission::factory()->companyPermissions()->count(5)->create();

        $expectedNames = ['view-companies', 'create-companies', 'edit-companies', 'delete-companies', 'manage-company-users'];

        expect($permissions)->toHaveCount(5);

        foreach ($expectedNames as $index => $expectedName) {
            expect($permissions[$index]->name)->toBe($expectedName);
        }
    });

    it('creates branch permissions using factory state', function () {
        $permissions = Permission::factory()->branchPermissions()->count(6)->create();

        $expectedNames = [
            'view-branches', 'create-branches', 'edit-branches',
            'delete-branches', 'manage-branch-status', 'filter-branches-by-company',
        ];

        expect($permissions)->toHaveCount(6);

        foreach ($expectedNames as $index => $expectedName) {
            expect($permissions[$index]->name)->toBe($expectedName);
        }
    });

    it('creates warehouse permissions using factory state', function () {
        $permissions = Permission::factory()->warehousePermissions()->count(8)->create();

        $expectedNames = [
            'view-warehouses', 'create-warehouses', 'edit-warehouses',
            'delete-warehouses', 'manage-warehouse-status', 'view-warehouse-capacity',
            'filter-warehouses-by-company', 'filter-warehouses-by-branch',
        ];

        expect($permissions)->toHaveCount(8);

        foreach ($expectedNames as $index => $expectedName) {
            expect($permissions[$index]->name)->toBe($expectedName);
        }
    });

    it('creates inventory permissions using factory state', function () {
        $permissions = Permission::factory()->inventoryPermissions()->count(6)->create();

        $expectedNames = [
            'view-inventory', 'create-inventory', 'edit-inventory',
            'delete-inventory', 'transfer-inventory', 'view-inventory-reports',
        ];

        expect($permissions)->toHaveCount(6);

        foreach ($expectedNames as $index => $expectedName) {
            expect($permissions[$index]->name)->toBe($expectedName);
        }
    });

    it('creates user permissions using factory state', function () {
        $permissions = Permission::factory()->userPermissions()->count(6)->create();

        $expectedNames = [
            'view-users', 'create-users', 'edit-users',
            'delete-users', 'assign-roles', 'manage-user-permissions',
        ];

        expect($permissions)->toHaveCount(6);

        foreach ($expectedNames as $index => $expectedName) {
            expect($permissions[$index]->name)->toBe($expectedName);
        }
    });

    it('creates view-only permissions using factory state', function () {
        $permissions = Permission::factory()->viewOnly()->count(6)->create();

        $expectedNames = [
            'view-branches', 'view-warehouses', 'view-warehouse-capacity',
            'view-inventory', 'view-inventory-reports', 'view-users',
        ];

        expect($permissions)->toHaveCount(6);

        foreach ($expectedNames as $index => $expectedName) {
            expect($permissions[$index]->name)->toBe($expectedName);
        }
    });

    it('creates administrative permissions using factory state', function () {
        $permissions = Permission::factory()->administrative()->count(5)->create();

        $expectedNames = [
            'create-companies', 'delete-companies', 'manage-company-users',
            'assign-roles', 'manage-user-permissions',
        ];

        expect($permissions)->toHaveCount(5);

        foreach ($expectedNames as $index => $expectedName) {
            expect($permissions[$index]->name)->toBe($expectedName);
        }
    });
});

describe('Permission Factory Helper Methods', function () {
    it('can create permission with specific name', function () {
        $permission = Permission::factory()->withName('custom-permission')->create();

        expect($permission->name)->toBe('custom-permission');
    });

    it('can create permission with specific guard', function () {
        $permission = Permission::factory()->withGuard('api')->create();

        expect($permission->guard_name)->toBe('api');
    });

    it('can combine name and guard modifiers', function () {
        $permission = Permission::factory()
            ->withName('api-permission')
            ->withGuard('api')
            ->create();

        expect($permission->name)->toBe('api-permission')
            ->and($permission->guard_name)->toBe('api');
    });
});

describe('Permission Role Relationships', function () {
    it('can assign permissions to roles', function () {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();

        $role->givePermissionTo($permission);

        expect($permission->roles)->toHaveCount(1)
            ->and($permission->roles->first()->id)->toBe($role->id);
    });

    it('can assign same permission to multiple roles', function () {
        $permission = Permission::factory()->create();
        $roles = Role::factory()->count(3)->create();

        $roles->each(fn ($role) => $role->givePermissionTo($permission));

        expect($permission->roles)->toHaveCount(3);
    });

    it('removes permission assignments when permission is deleted', function () {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();

        $role->givePermissionTo($permission);
        expect($role->hasPermissionTo($permission))->toBeTrue();

        $permission->delete();
        $role->refresh();

        expect($role->permissions)->toHaveCount(0);
    });
});

describe('Permission User Relationships', function () {
    it('can assign permissions directly to users', function () {
        $permission = Permission::factory()->create();
        $user = User::factory()->create();

        $user->givePermissionTo($permission);

        expect($user->hasDirectPermission($permission))->toBeTrue()
            ->and($user->hasPermissionTo($permission))->toBeTrue();
    });

    it('can revoke direct permissions from users', function () {
        $permission = Permission::factory()->create();
        $user = User::factory()->create();

        $user->givePermissionTo($permission);
        expect($user->hasPermissionTo($permission))->toBeTrue();

        $user->revokePermissionTo($permission);
        expect($user->hasPermissionTo($permission))->toBeFalse();
    });

    it('distinguishes between direct and role permissions', function () {
        $permission = Permission::factory()->create();
        $role = Role::factory()->create();
        $user = User::factory()->create();

        // Give permission via role
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        expect($user->hasPermissionTo($permission))->toBeTrue()
            ->and($user->hasDirectPermission($permission))->toBeFalse();

        // Give permission directly
        $user->givePermissionTo($permission);

        expect($user->hasDirectPermission($permission))->toBeTrue();
    });
});

describe('Seeded Permissions Validation', function () {
    it('has all required system permissions created by seeder', function () {
        $requiredPermissions = [
            // Branch permissions
            'view-branches', 'create-branches', 'edit-branches',
            'delete-branches', 'manage-branch-status', 'filter-branches-by-company',

            // Warehouse permissions
            'view-warehouses', 'create-warehouses', 'edit-warehouses',
            'delete-warehouses', 'manage-warehouse-status', 'view-warehouse-capacity',
            'filter-warehouses-by-company', 'filter-warehouses-by-branch',

            // Company permissions
            'view-companies', 'create-companies', 'edit-companies',
            'delete-companies', 'manage-company-users',

            // Inventory permissions
            'view-inventory', 'create-inventory', 'edit-inventory',
            'delete-inventory', 'transfer-inventory', 'view-inventory-reports',

            // User permissions
            'view-users', 'create-users', 'edit-users',
            'delete-users', 'assign-roles', 'manage-user-permissions',
        ];

        foreach ($requiredPermissions as $permissionName) {
            expect(Permission::where('name', $permissionName)->exists())
                ->toBeTrue("Permission '{$permissionName}' should exist");
        }

        // Check total count matches expectation
        expect(Permission::count())->toBe(count($requiredPermissions));
    });
});

describe('Permission Grouping and Categories', function () {
    it('can identify view permissions', function () {
        $viewPermissions = Permission::where('name', 'like', 'view-%')->get();

        $expectedViewPermissions = [
            'view-branches', 'view-warehouses', 'view-warehouse-capacity',
            'view-inventory', 'view-inventory-reports', 'view-users', 'view-companies',
        ];

        expect($viewPermissions)->toHaveCount(count($expectedViewPermissions));

        foreach ($expectedViewPermissions as $permissionName) {
            expect($viewPermissions->pluck('name'))->toContain($permissionName);
        }
    });

    it('can identify create permissions', function () {
        $createPermissions = Permission::where('name', 'like', 'create-%')->get();

        $expectedCreatePermissions = [
            'create-branches', 'create-warehouses', 'create-companies',
            'create-inventory', 'create-users',
        ];

        expect($createPermissions)->toHaveCount(count($expectedCreatePermissions));

        foreach ($expectedCreatePermissions as $permissionName) {
            expect($createPermissions->pluck('name'))->toContain($permissionName);
        }
    });

    it('can identify edit permissions', function () {
        $editPermissions = Permission::where('name', 'like', 'edit-%')->get();

        $expectedEditPermissions = [
            'edit-branches', 'edit-warehouses', 'edit-companies',
            'edit-inventory', 'edit-users',
        ];

        expect($editPermissions)->toHaveCount(count($expectedEditPermissions));

        foreach ($expectedEditPermissions as $permissionName) {
            expect($editPermissions->pluck('name'))->toContain($permissionName);
        }
    });

    it('can identify delete permissions', function () {
        $deletePermissions = Permission::where('name', 'like', 'delete-%')->get();

        $expectedDeletePermissions = [
            'delete-branches', 'delete-warehouses', 'delete-companies',
            'delete-inventory', 'delete-users',
        ];

        expect($deletePermissions)->toHaveCount(count($expectedDeletePermissions));

        foreach ($expectedDeletePermissions as $permissionName) {
            expect($deletePermissions->pluck('name'))->toContain($permissionName);
        }
    });
});

describe('Permission Validation Edge Cases', function () {
    it('handles empty string name gracefully', function () {
        expect(fn () => Permission::factory()->withName('')->create())
            ->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('handles very long permission names', function () {
        $longName = str_repeat('a', 255);
        $permission = Permission::factory()->withName($longName)->create();

        expect($permission->name)->toBe($longName);
    });

    it('handles special characters in permission names', function () {
        $specialName = 'permission-with-special_chars.123';
        $permission = Permission::factory()->withName($specialName)->create();

        expect($permission->name)->toBe($specialName);
    });
});
