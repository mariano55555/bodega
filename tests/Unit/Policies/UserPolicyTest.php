<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;

use function Pest\Laravel\seed;

beforeEach(function () {
    seed([\Database\Seeders\RolesAndPermissionsSeeder::class]);

    $this->company1 = Company::factory()->create(['name' => 'Company A']);
    $this->company2 = Company::factory()->create(['name' => 'Company B']);

    $this->branch1 = Branch::factory()->forCompany($this->company1)->create();
    $this->branch2 = Branch::factory()->forCompany($this->company2)->create();

    $this->superAdmin = User::factory()->forCompany($this->company1)->superAdmin()->create();
    $this->companyAdmin1 = User::factory()->forCompany($this->company1)->companyAdmin()->create();
    $this->companyAdmin2 = User::factory()->forCompany($this->company2)->companyAdmin()->create();
    $this->branchManager = User::factory()->forBranch($this->branch1)->branchManager()->create();
    $this->warehouseManager = User::factory()->forBranch($this->branch1)->warehouseManager()->create();
    $this->warehouseOperator = User::factory()->forBranch($this->branch1)->warehouseOperator()->create();

    $this->targetUser1 = User::factory()->forCompany($this->company1)->create();
    $this->targetUser2 = User::factory()->forCompany($this->company2)->create();
});

describe('User Management Permissions', function () {
    it('allows super admin to manage all users', function () {
        expect($this->superAdmin->can('view', $this->targetUser1))->toBeTrue()
            ->and($this->superAdmin->can('view', $this->targetUser2))->toBeTrue()
            ->and($this->superAdmin->hasPermissionTo('create-users'))->toBeTrue()
            ->and($this->superAdmin->hasPermissionTo('edit-users'))->toBeTrue()
            ->and($this->superAdmin->hasPermissionTo('delete-users'))->toBeTrue();
    });

    it('allows company admin to manage users in their company', function () {
        expect($this->companyAdmin1->hasPermissionTo('view-users'))->toBeTrue()
            ->and($this->companyAdmin1->hasPermissionTo('create-users'))->toBeTrue()
            ->and($this->companyAdmin1->hasPermissionTo('edit-users'))->toBeTrue()
            ->and($this->companyAdmin1->hasPermissionTo('assign-roles'))->toBeTrue();
    });

    it('restricts company admin to their own company users', function () {
        // Company admin 1 should access company 1 users
        expect($this->companyAdmin1->canAccessCompany($this->company1->id))->toBeTrue()
            ->and($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse();

        // Company admin 2 should access company 2 users
        expect($this->companyAdmin2->canAccessCompany($this->company2->id))->toBeTrue()
            ->and($this->companyAdmin2->canAccessCompany($this->company1->id))->toBeFalse();
    });

    it('denies user management to warehouse operators', function () {
        expect($this->warehouseOperator->hasPermissionTo('create-users'))->toBeFalse()
            ->and($this->warehouseOperator->hasPermissionTo('edit-users'))->toBeFalse()
            ->and($this->warehouseOperator->hasPermissionTo('delete-users'))->toBeFalse()
            ->and($this->warehouseOperator->hasPermissionTo('assign-roles'))->toBeFalse();
    });

    it('allows warehouse operators to view users only', function () {
        expect($this->warehouseOperator->hasPermissionTo('view-users'))->toBeTrue();
    });
});

describe('Role Assignment Permissions', function () {
    it('allows super admin to assign any role', function () {
        expect($this->superAdmin->hasPermissionTo('assign-roles'))->toBeTrue()
            ->and($this->superAdmin->hasPermissionTo('manage-user-permissions'))->toBeTrue();
    });

    it('allows company admin to assign limited roles', function () {
        expect($this->companyAdmin1->hasPermissionTo('assign-roles'))->toBeTrue();
    });

    it('denies role assignment to lower-level users', function () {
        expect($this->branchManager->hasPermissionTo('assign-roles'))->toBeFalse()
            ->and($this->warehouseManager->hasPermissionTo('assign-roles'))->toBeFalse()
            ->and($this->warehouseOperator->hasPermissionTo('assign-roles'))->toBeFalse();
    });
});

describe('Cross-Company User Access Security', function () {
    it('prevents users from accessing other company data', function () {
        // Test that company 1 users can't access company 2 data
        expect($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse()
            ->and($this->branchManager->canAccessCompany($this->company2->id))->toBeFalse()
            ->and($this->warehouseManager->canAccessCompany($this->company2->id))->toBeFalse()
            ->and($this->warehouseOperator->canAccessCompany($this->company2->id))->toBeFalse();
    });

    it('ensures proper company isolation for user queries', function () {
        // Create multiple users in each company
        $company1Users = User::factory()->forCompany($this->company1)->count(3)->create();
        $company2Users = User::factory()->forCompany($this->company2)->count(3)->create();

        // Company admin should only see their company users
        $accessibleUsersCount1 = User::where('company_id', $this->company1->id)->count();
        $accessibleUsersCount2 = User::where('company_id', $this->company2->id)->count();

        expect($accessibleUsersCount1)->toBeGreaterThan(0)
            ->and($accessibleUsersCount2)->toBeGreaterThan(0);

        // Verify company admins can only access their own company
        expect($this->companyAdmin1->canAccessCompany($this->company1->id))->toBeTrue()
            ->and($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse();
    });
});

describe('Branch and Warehouse Level User Access', function () {
    it('restricts branch managers to their branch', function () {
        expect($this->branchManager->canAccessBranch($this->branch1->id))->toBeTrue()
            ->and($this->branchManager->canAccessBranch($this->branch2->id))->toBeFalse();
    });

    it('restricts warehouse roles to their assigned branch', function () {
        expect($this->warehouseManager->canAccessBranch($this->branch1->id))->toBeTrue()
            ->and($this->warehouseManager->canAccessBranch($this->branch2->id))->toBeFalse()
            ->and($this->warehouseOperator->canAccessBranch($this->branch1->id))->toBeTrue()
            ->and($this->warehouseOperator->canAccessBranch($this->branch2->id))->toBeFalse();
    });
});

describe('User Self-Management', function () {
    it('allows users to view their own profile', function () {
        $user = User::factory()->forCompany($this->company1)->create();

        expect($user->can('view', $user))->toBeTrue();
    });

    it('allows users to update their own basic information', function () {
        $user = User::factory()->forCompany($this->company1)->create();

        // Users should be able to update their own profile
        expect($user->can('update', $user))->toBeTrue();
    });

    it('prevents users from changing their own roles', function () {
        $user = User::factory()->forCompany($this->company1)->create();

        // Regular users should not be able to assign roles to themselves
        expect($user->hasPermissionTo('assign-roles'))->toBeFalse();
    });
});

describe('Hierarchical User Management', function () {
    it('allows company admin to manage branch managers', function () {
        expect($this->companyAdmin1->canAccessCompany($this->branchManager->company_id))->toBeTrue()
            ->and($this->companyAdmin1->hasPermissionTo('edit-users'))->toBeTrue();
    });

    it('allows company admin to manage warehouse staff', function () {
        expect($this->companyAdmin1->canAccessCompany($this->warehouseManager->company_id))->toBeTrue()
            ->and($this->companyAdmin1->canAccessCompany($this->warehouseOperator->company_id))->toBeTrue();
    });

    it('prevents lower-level users from managing higher-level users', function () {
        // Branch manager should not be able to modify company admin
        expect($this->branchManager->hasPermissionTo('edit-users'))->toBeFalse();

        // Warehouse roles should not be able to modify branch managers
        expect($this->warehouseManager->hasPermissionTo('edit-users'))->toBeFalse()
            ->and($this->warehouseOperator->hasPermissionTo('edit-users'))->toBeFalse();
    });
});

describe('User Deactivation and Security', function () {
    it('handles user deactivation properly', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        expect($user->hasPermissionTo('edit-users'))->toBeTrue();

        // Remove role (simulate deactivation)
        $user->removeRole('company-admin');

        expect($user->hasPermissionTo('edit-users'))->toBeFalse();
    });

    it('prevents deleted users from maintaining access', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        expect($user->canAccessCompany($this->company1->id))->toBeTrue();

        // Soft delete the user
        $user->delete();

        // User should lose access upon deletion
        expect($user->trashed())->toBeTrue();
    });
});

describe('User Role Inheritance and Permissions', function () {
    it('grants permissions based on highest role level', function () {
        $user = User::factory()->forCompany($this->company1)->create();

        // Assign multiple roles
        $user->assignRole(['warehouse-operator', 'branch-manager']);

        // Should have permissions from the highest role (branch-manager)
        expect($user->hasPermissionTo('view-users'))->toBeTrue()
            ->and($user->hasPermissionTo('edit-warehouses'))->toBeTrue();
    });

    it('accumulates permissions from multiple roles', function () {
        $user = User::factory()->forCompany($this->company1)->create();

        // Assign roles that have different permission sets
        $user->assignRole(['warehouse-manager', 'branch-manager']);

        // Should have permissions from both roles
        expect($user->hasPermissionTo('view-inventory'))->toBeTrue()
            ->and($user->hasPermissionTo('manage-warehouse-status'))->toBeTrue()
            ->and($user->hasPermissionTo('manage-branch-status'))->toBeTrue();
    });
});

describe('Edge Cases and Security Validation', function () {
    it('handles users without company assignment', function () {
        $userWithoutCompany = User::factory()->create(['company_id' => null]);

        expect($userWithoutCompany->canAccessCompany($this->company1->id))->toBeFalse()
            ->and($userWithoutCompany->accessibleBranches()->count())->toBe(0)
            ->and($userWithoutCompany->accessibleWarehouses()->count())->toBe(0);
    });

    it('handles users without branch assignment for branch-level roles', function () {
        $userWithoutBranch = User::factory()->forCompany($this->company1)->create(['branch_id' => null]);
        $userWithoutBranch->assignRole('branch-manager');

        expect($userWithoutBranch->canAccessBranch($this->branch1->id))->toBeFalse();
    });

    it('prevents privilege escalation through role manipulation', function () {
        $user = User::factory()->forCompany($this->company1)->warehouseOperator()->create();

        expect($user->hasPermissionTo('assign-roles'))->toBeFalse();

        // Even if they somehow gain access to the system, they shouldn't be able to assign roles
        expect($user->hasRole('super-admin'))->toBeFalse()
            ->and($user->hasRole('company-admin'))->toBeFalse();
    });
});
