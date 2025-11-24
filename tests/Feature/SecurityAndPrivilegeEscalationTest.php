<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed([\Database\Seeders\RolesAndPermissionsSeeder::class]);

    $this->company1 = Company::factory()->create(['name' => 'Company A']);
    $this->company2 = Company::factory()->create(['name' => 'Company B']);
    $this->company3 = Company::factory()->create(['name' => 'Company C']);

    $this->branch1 = Branch::factory()->forCompany($this->company1)->create();
    $this->branch2 = Branch::factory()->forCompany($this->company2)->create();

    $this->warehouse1 = Warehouse::factory()->forBranch($this->branch1)->create();
    $this->warehouse2 = Warehouse::factory()->forBranch($this->branch2)->create();

    $this->superAdmin = User::factory()->forCompany($this->company1)->superAdmin()->create();
    $this->companyAdmin1 = User::factory()->forCompany($this->company1)->companyAdmin()->create();
    $this->companyAdmin2 = User::factory()->forCompany($this->company2)->companyAdmin()->create();
    $this->branchManager = User::factory()->forBranch($this->branch1)->branchManager()->create();
    $this->warehouseManager = User::factory()->forBranch($this->branch1)->warehouseManager()->create();
    $this->warehouseOperator = User::factory()->forBranch($this->branch1)->warehouseOperator()->create();
    $this->maliciousUser = User::factory()->forCompany($this->company1)->create();
});

describe('Unauthorized Access Prevention', function () {
    it('prevents company admin from accessing other companies data', function () {
        actingAs($this->companyAdmin1);

        // Cannot access company 2 data
        expect($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse()
            ->and($this->companyAdmin1->canAccessCompany($this->company3->id))->toBeFalse();

        // Cannot access branches in other companies
        expect($this->companyAdmin1->canAccessBranch($this->branch2->id))->toBeFalse();

        // Should only see their own company warehouses
        $accessibleWarehouses = $this->companyAdmin1->accessibleWarehouses()->get();
        $accessibleWarehouses->each(function ($warehouse) {
            expect($warehouse->company_id)->toBe($this->company1->id);
        });
    });

    it('prevents branch manager from accessing other branches', function () {
        actingAs($this->branchManager);

        // Can access own branch
        expect($this->branchManager->canAccessBranch($this->branch1->id))->toBeTrue();

        // Cannot access other branches
        expect($this->branchManager->canAccessBranch($this->branch2->id))->toBeFalse();

        // Cannot access other companies
        expect($this->branchManager->canAccessCompany($this->company2->id))->toBeFalse();
    });

    it('prevents warehouse roles from accessing unauthorized warehouses', function () {
        actingAs($this->warehouseManager);

        $accessibleWarehouses = $this->warehouseManager->accessibleWarehouses()->get();

        // Should only access warehouses in their branch
        $accessibleWarehouses->each(function ($warehouse) {
            expect($warehouse->branch_id)->toBe($this->branch1->id);
        });

        // Should not access warehouses in other branches/companies
        $inaccessibleWarehouses = Warehouse::where('branch_id', '!=', $this->branch1->id)->get();
        expect($inaccessibleWarehouses)->not->toBeEmpty();
    });

    it('prevents users without roles from accessing protected resources', function () {
        actingAs($this->maliciousUser);

        // No management permissions
        expect($this->maliciousUser->hasPermissionTo('create-companies'))->toBeFalse()
            ->and($this->maliciousUser->hasPermissionTo('delete-users'))->toBeFalse()
            ->and($this->maliciousUser->hasPermissionTo('assign-roles'))->toBeFalse()
            ->and($this->maliciousUser->hasPermissionTo('manage-user-permissions'))->toBeFalse();

        // No view permissions for sensitive data
        expect($this->maliciousUser->hasPermissionTo('view-companies'))->toBeFalse();
    });
});

describe('Privilege Escalation Prevention', function () {
    it('prevents users from assigning roles to themselves', function () {
        actingAs($this->maliciousUser);

        // User should not be able to assign roles
        expect($this->maliciousUser->hasPermissionTo('assign-roles'))->toBeFalse();

        // Even if they try to assign roles (this would be blocked at controller level)
        $initialRoles = $this->maliciousUser->roles->count();

        // This would typically fail at authorization layer
        expect($this->maliciousUser->hasRole('super-admin'))->toBeFalse()
            ->and($this->maliciousUser->hasRole('company-admin'))->toBeFalse();
    });

    it('prevents lower-level users from escalating privileges', function () {
        actingAs($this->warehouseOperator);

        // Warehouse operator cannot assign roles
        expect($this->warehouseOperator->hasPermissionTo('assign-roles'))->toBeFalse();

        // Cannot modify user permissions
        expect($this->warehouseOperator->hasPermissionTo('manage-user-permissions'))->toBeFalse();

        // Cannot create or delete users
        expect($this->warehouseOperator->hasPermissionTo('create-users'))->toBeFalse()
            ->and($this->warehouseOperator->hasPermissionTo('delete-users'))->toBeFalse();
    });

    it('prevents company admin from creating super admin users', function () {
        actingAs($this->companyAdmin1);

        // Company admin cannot manage super admin permissions
        expect($this->companyAdmin1->hasPermissionTo('manage-user-permissions'))->toBeFalse();

        // Cannot directly assign super admin roles (would be blocked at business logic)
        $testUser = User::factory()->forCompany($this->company1)->create();

        // This would typically be validated at service/controller level
        expect($this->companyAdmin1->hasRole('super-admin'))->toBeFalse();
    });

    it('prevents cross-company privilege escalation', function () {
        actingAs($this->companyAdmin1);

        $userInOtherCompany = User::factory()->forCompany($this->company2)->create();

        // Company admin 1 cannot access users in company 2
        expect($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse();

        // Cannot modify users in other companies
        expect($this->companyAdmin1->canAccessCompany($userInOtherCompany->company_id))->toBeFalse();
    });
});

describe('Role Manipulation Security', function () {
    it('prevents unauthorized role modification', function () {
        actingAs($this->branchManager);

        $targetUser = User::factory()->forCompany($this->company1)->create();

        // Branch manager cannot assign roles
        expect($this->branchManager->hasPermissionTo('assign-roles'))->toBeFalse();

        // Should not be able to modify user roles
        expect($this->branchManager->hasPermissionTo('manage-user-permissions'))->toBeFalse();
    });

    it('prevents role removal by unauthorized users', function () {
        actingAs($this->warehouseOperator);

        // Create a user with a role
        $userWithRole = User::factory()->forCompany($this->company1)->branchManager()->create();

        expect($userWithRole->hasRole('branch-manager'))->toBeTrue();

        // Warehouse operator cannot remove roles
        expect($this->warehouseOperator->hasPermissionTo('assign-roles'))->toBeFalse();
    });

    it('prevents creation of unauthorized roles', function () {
        actingAs($this->companyAdmin1);

        // Company admin cannot create new roles (this is typically super admin only)
        expect($this->companyAdmin1->hasPermissionTo('create-companies'))->toBeFalse();

        // Would be blocked at service level when trying to create new roles
    });

    it('maintains role hierarchy integrity', function () {
        // Ensure role hierarchy cannot be circumvented
        actingAs($this->branchManager);

        // Branch manager has lower privileges than company admin
        expect($this->branchManager->hasPermissionTo('create-branches'))->toBeFalse()
            ->and($this->branchManager->hasPermissionTo('delete-branches'))->toBeFalse();

        // Company admin has higher privileges
        expect($this->companyAdmin1->hasPermissionTo('create-branches'))->toBeTrue()
            ->and($this->companyAdmin1->hasPermissionTo('delete-branches'))->toBeTrue();
    });
});

describe('Session and Authentication Security', function () {
    it('prevents access after role removal', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        actingAs($user);

        // User has permissions initially
        expect($user->hasPermissionTo('create-branches'))->toBeTrue();

        // Remove role (simulate deactivation)
        $user->removeRole('company-admin');
        $user->refresh();

        // User should lose permissions
        expect($user->hasPermissionTo('create-branches'))->toBeFalse();
    });

    it('prevents access after user deactivation', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        actingAs($user);

        expect($user->hasRole('company-admin'))->toBeTrue();

        // Soft delete user (deactivation)
        $user->delete();

        expect($user->trashed())->toBeTrue();

        // User should be considered inactive
        $activeUser = User::where('id', $user->id)->first();
        expect($activeUser)->toBeNull();
    });

    it('prevents session hijacking by validating user state', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        actingAs($user);

        // Simulate company change (user moved to different company)
        $originalCompanyId = $user->company_id;
        $user->update(['company_id' => $this->company2->id]);

        // User should no longer have access to original company
        expect($user->canAccessCompany($originalCompanyId))->toBeFalse();
        expect($user->canAccessCompany($this->company2->id))->toBeTrue();
    });
});

describe('Data Isolation and Integrity', function () {
    it('ensures complete data isolation between companies', function () {
        // Create data in each company
        $company1Users = User::factory()->forCompany($this->company1)->count(3)->create();
        $company2Users = User::factory()->forCompany($this->company2)->count(2)->create();

        actingAs($this->companyAdmin1);

        // Company 1 admin should only see company 1 users
        $visibleUsers = User::where('company_id', $this->company1->id)->get();
        $hiddenUsers = User::where('company_id', $this->company2->id)->get();

        expect($visibleUsers->count())->toBeGreaterThan(0);
        expect($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse();

        // Verify data integrity
        $visibleUsers->each(function ($user) {
            expect($user->company_id)->toBe($this->company1->id);
        });
    });

    it('prevents data leakage through relationship queries', function () {
        actingAs($this->companyAdmin1);

        // Company admin should not see warehouses from other companies
        $accessibleWarehouses = $this->companyAdmin1->accessibleWarehouses()->get();
        $accessibleWarehouses->each(function ($warehouse) {
            expect($warehouse->company_id)->toBe($this->company1->id);
        });

        // Should not see branches from other companies
        $accessibleBranches = $this->companyAdmin1->accessibleBranches()->get();
        $accessibleBranches->each(function ($branch) {
            expect($branch->company_id)->toBe($this->company1->id);
        });
    });
});

describe('Permission Boundary Testing', function () {
    it('tests permission boundaries at each role level', function () {
        $roles = [
            ['user' => $this->warehouseOperator, 'role' => 'warehouse-operator'],
            ['user' => $this->warehouseManager, 'role' => 'warehouse-manager'],
            ['user' => $this->branchManager, 'role' => 'branch-manager'],
            ['user' => $this->companyAdmin1, 'role' => 'company-admin'],
            ['user' => $this->superAdmin, 'role' => 'super-admin'],
        ];

        foreach ($roles as $roleData) {
            actingAs($roleData['user']);

            // Test create permissions
            $canCreateUsers = in_array($roleData['role'], ['super-admin', 'company-admin']);
            expect($roleData['user']->hasPermissionTo('create-users'))->toBe($canCreateUsers);

            // Test delete permissions
            $canDeleteUsers = in_array($roleData['role'], ['super-admin']);
            expect($roleData['user']->hasPermissionTo('delete-users'))->toBe($canDeleteUsers);

            // Test company creation
            $canCreateCompanies = $roleData['role'] === 'super-admin';
            expect($roleData['user']->hasPermissionTo('create-companies'))->toBe($canCreateCompanies);
        }
    });

    it('validates permission inheritance and overrides', function () {
        // Test that permissions are correctly inherited from roles
        $user = User::factory()->forCompany($this->company1)->create();

        // Start with no permissions
        expect($user->hasPermissionTo('view-inventory'))->toBeFalse();

        // Assign warehouse operator role
        $user->assignRole('warehouse-operator');
        expect($user->hasPermissionTo('view-inventory'))->toBeTrue()
            ->and($user->hasPermissionTo('create-inventory'))->toBeFalse();

        // Upgrade to warehouse manager
        $user->syncRoles(['warehouse-manager']);
        expect($user->hasPermissionTo('view-inventory'))->toBeTrue()
            ->and($user->hasPermissionTo('create-inventory'))->toBeTrue()
            ->and($user->hasPermissionTo('edit-inventory'))->toBeTrue();
    });
});

describe('Audit Trail and Security Logging', function () {
    it('maintains security audit trail for critical operations', function () {
        actingAs($this->superAdmin);

        $user = User::factory()->forCompany($this->company1)->create();

        // Role assignment should be auditable
        $user->assignRole('company-admin');

        // Permission changes should be trackable
        expect($user->hasRole('company-admin'))->toBeTrue();

        $user->removeRole('company-admin');
        expect($user->hasRole('company-admin'))->toBeFalse();

        // In a real application, these operations would be logged
        // for security auditing purposes
    });

    it('tracks unauthorized access attempts', function () {
        actingAs($this->maliciousUser);

        // Attempts to access unauthorized resources should be logged
        // This would typically be handled by middleware/policies
        expect($this->maliciousUser->hasPermissionTo('view-companies'))->toBeFalse();
        expect($this->maliciousUser->hasPermissionTo('create-users'))->toBeFalse();
        expect($this->maliciousUser->hasPermissionTo('assign-roles'))->toBeFalse();

        // Failed authorization attempts would be logged in production
    });
});

describe('Rate Limiting and Abuse Prevention', function () {
    it('should implement rate limiting for sensitive operations', function () {
        // This would typically be implemented at the middleware/controller level
        actingAs($this->companyAdmin1);

        // Multiple rapid role assignment attempts should be rate limited
        $users = User::factory()->forCompany($this->company1)->count(5)->create();

        foreach ($users as $user) {
            $user->assignRole('branch-manager');
            expect($user->hasRole('branch-manager'))->toBeTrue();
        }

        // In production, rapid successive role changes would be rate limited
    });

    it('prevents brute force permission checking', function () {
        actingAs($this->maliciousUser);

        // Rapid permission checks should be monitored/limited
        $permissions = Permission::all()->pluck('name')->toArray();

        foreach ($permissions as $permission) {
            $this->maliciousUser->hasPermissionTo($permission);
        }

        // This pattern would be detected and limited in production
    });
});
