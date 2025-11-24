<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\seed;

beforeEach(function () {
    seed([\Database\Seeders\RolesAndPermissionsSeeder::class]);

    $this->company1 = Company::factory()->create(['name' => 'Company A']);
    $this->company2 = Company::factory()->create(['name' => 'Company B']);

    $this->branch1 = Branch::factory()->forCompany($this->company1)->create();
    $this->branch2 = Branch::factory()->forCompany($this->company2)->create();

    $this->warehouse1 = Warehouse::factory()->forBranch($this->branch1)->create();
    $this->warehouse2 = Warehouse::factory()->forBranch($this->branch2)->create();
});

describe('User Role Checks', function () {
    it('correctly identifies super admin users', function () {
        $user = User::factory()->forCompany($this->company1)->superAdmin()->create();

        expect($user->isSuperAdmin())->toBeTrue()
            ->and($user->isCompanyAdmin())->toBeFalse()
            ->and($user->isBranchManager())->toBeFalse()
            ->and($user->isWarehouseManager())->toBeFalse()
            ->and($user->isWarehouseOperator())->toBeFalse();
    });

    it('correctly identifies company admin users', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        expect($user->isCompanyAdmin())->toBeTrue()
            ->and($user->isSuperAdmin())->toBeFalse()
            ->and($user->isBranchManager())->toBeFalse()
            ->and($user->isWarehouseManager())->toBeFalse()
            ->and($user->isWarehouseOperator())->toBeFalse();
    });

    it('correctly identifies branch manager users', function () {
        $user = User::factory()->forBranch($this->branch1)->branchManager()->create();

        expect($user->isBranchManager())->toBeTrue()
            ->and($user->isSuperAdmin())->toBeFalse()
            ->and($user->isCompanyAdmin())->toBeFalse()
            ->and($user->isWarehouseManager())->toBeFalse()
            ->and($user->isWarehouseOperator())->toBeFalse();
    });

    it('correctly identifies warehouse manager users', function () {
        $user = User::factory()->forBranch($this->branch1)->warehouseManager()->create();

        expect($user->isWarehouseManager())->toBeTrue()
            ->and($user->isSuperAdmin())->toBeFalse()
            ->and($user->isCompanyAdmin())->toBeFalse()
            ->and($user->isBranchManager())->toBeFalse()
            ->and($user->isWarehouseOperator())->toBeFalse();
    });

    it('correctly identifies warehouse operator users', function () {
        $user = User::factory()->forBranch($this->branch1)->warehouseOperator()->create();

        expect($user->isWarehouseOperator())->toBeTrue()
            ->and($user->isSuperAdmin())->toBeFalse()
            ->and($user->isCompanyAdmin())->toBeFalse()
            ->and($user->isBranchManager())->toBeFalse()
            ->and($user->isWarehouseManager())->toBeFalse();
    });
});

describe('Company Access Control', function () {
    it('allows super admin to access all companies', function () {
        $user = User::factory()->forCompany($this->company1)->superAdmin()->create();

        expect($user->canAccessCompany($this->company1->id))->toBeTrue()
            ->and($user->canAccessCompany($this->company2->id))->toBeTrue();
    });

    it('allows users to access only their own company', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        expect($user->canAccessCompany($this->company1->id))->toBeTrue()
            ->and($user->canAccessCompany($this->company2->id))->toBeFalse();
    });

    it('denies access to companies for users without company assignment', function () {
        $user = User::factory()->create(['company_id' => null]);

        expect($user->canAccessCompany($this->company1->id))->toBeFalse()
            ->and($user->canAccessCompany($this->company2->id))->toBeFalse();
    });
});

describe('Branch Access Control', function () {
    it('allows super admin to access all branches', function () {
        $user = User::factory()->forCompany($this->company1)->superAdmin()->create();

        expect($user->canAccessBranch($this->branch1->id))->toBeTrue()
            ->and($user->canAccessBranch($this->branch2->id))->toBeTrue();
    });

    it('allows company admin to access all branches in their company', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        expect($user->canAccessBranch($this->branch1->id))->toBeTrue()
            ->and($user->canAccessBranch($this->branch2->id))->toBeFalse();
    });

    it('allows branch manager to access only their assigned branch', function () {
        $user = User::factory()->forBranch($this->branch1)->branchManager()->create();

        expect($user->canAccessBranch($this->branch1->id))->toBeTrue()
            ->and($user->canAccessBranch($this->branch2->id))->toBeFalse();
    });

    it('allows warehouse roles to access branches with their warehouses', function () {
        $user = User::factory()->forBranch($this->branch1)->warehouseManager()->create();

        expect($user->canAccessBranch($this->branch1->id))->toBeTrue()
            ->and($user->canAccessBranch($this->branch2->id))->toBeFalse();
    });
});

describe('Warehouse Access Queries', function () {
    it('returns all warehouses for super admin', function () {
        $user = User::factory()->forCompany($this->company1)->superAdmin()->create();

        $accessibleCount = $user->accessibleWarehouses()->count();
        $totalCount = Warehouse::count();

        expect($accessibleCount)->toBe($totalCount);
    });

    it('returns company warehouses for company admin', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        $accessibleWarehouses = $user->accessibleWarehouses()->get();

        expect($accessibleWarehouses)->toHaveCount(1)
            ->and($accessibleWarehouses->first()->id)->toBe($this->warehouse1->id);
    });

    it('returns branch warehouses for branch manager', function () {
        $user = User::factory()->forBranch($this->branch1)->branchManager()->create();

        $accessibleWarehouses = $user->accessibleWarehouses()->get();

        expect($accessibleWarehouses)->toHaveCount(1)
            ->and($accessibleWarehouses->first()->id)->toBe($this->warehouse1->id);
    });

    it('returns no warehouses for users without proper assignment', function () {
        $user = User::factory()->create(['company_id' => null, 'branch_id' => null]);

        $accessibleCount = $user->accessibleWarehouses()->count();

        expect($accessibleCount)->toBe(0);
    });
});

describe('Branch Access Queries', function () {
    it('returns all branches for super admin', function () {
        $user = User::factory()->forCompany($this->company1)->superAdmin()->create();

        $accessibleCount = $user->accessibleBranches()->count();
        $totalCount = Branch::count();

        expect($accessibleCount)->toBe($totalCount);
    });

    it('returns company branches for company admin', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        $accessibleBranches = $user->accessibleBranches()->get();

        expect($accessibleBranches)->toHaveCount(1)
            ->and($accessibleBranches->first()->id)->toBe($this->branch1->id);
    });

    it('returns only assigned branch for lower-level roles', function () {
        $user = User::factory()->forBranch($this->branch1)->branchManager()->create();

        $accessibleBranches = $user->accessibleBranches()->get();

        expect($accessibleBranches)->toHaveCount(1)
            ->and($accessibleBranches->first()->id)->toBe($this->branch1->id);
    });
});

describe('Permission Checks', function () {
    it('can assign and check specific permissions', function () {
        $user = User::factory()->forCompany($this->company1)->create();
        $permission = Permission::factory()->withName('test-permission')->create();

        expect($user->hasPermissionTo('test-permission'))->toBeFalse();

        $user->givePermissionTo('test-permission');

        expect($user->hasPermissionTo('test-permission'))->toBeTrue();
    });

    it('inherits permissions from assigned roles', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        expect($user->hasPermissionTo('view-branches'))->toBeTrue()
            ->and($user->hasPermissionTo('create-branches'))->toBeTrue()
            ->and($user->hasPermissionTo('view-companies'))->toBeTrue();
    });

    it('can check multiple permissions at once', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        expect($user->hasAllPermissions(['view-branches', 'create-branches']))->toBeTrue()
            ->and($user->hasAnyPermission(['view-branches', 'nonexistent-permission']))->toBeTrue()
            ->and($user->hasAnyPermission(['nonexistent-permission-1', 'nonexistent-permission-2']))->toBeFalse();
    });
});

describe('Role Management', function () {
    it('can assign and remove roles', function () {
        $user = User::factory()->forCompany($this->company1)->create();

        expect($user->hasRole('company-admin'))->toBeFalse();

        $user->assignRole('company-admin');

        expect($user->hasRole('company-admin'))->toBeTrue();

        $user->removeRole('company-admin');

        expect($user->hasRole('company-admin'))->toBeFalse();
    });

    it('can have multiple roles simultaneously', function () {
        $user = User::factory()->forCompany($this->company1)->create();
        $customRole = Role::factory()->withName('custom-role')->create();

        $user->assignRole(['company-admin', 'custom-role']);

        expect($user->hasRole('company-admin'))->toBeTrue()
            ->and($user->hasRole('custom-role'))->toBeTrue()
            ->and($user->roles)->toHaveCount(2);
    });

    it('can sync roles replacing existing ones', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        expect($user->hasRole('company-admin'))->toBeTrue();

        $user->syncRoles(['branch-manager']);

        expect($user->hasRole('company-admin'))->toBeFalse()
            ->and($user->hasRole('branch-manager'))->toBeTrue()
            ->and($user->roles)->toHaveCount(1);
    });
});

describe('User Display Methods', function () {
    it('generates correct initials for user names', function () {
        $user = User::factory()->create(['name' => 'John Doe Smith']);

        expect($user->initials())->toBe('JD');
    });

    it('handles single name for initials', function () {
        $user = User::factory()->create(['name' => 'Madonna']);

        expect($user->initials())->toBe('M');
    });

    it('uses profile full name as display name when available', function () {
        $user = User::factory()->create(['name' => 'John Doe']);
        $user->profile()->create([
            'full_name' => 'John Doe Smith Jr.',
            'primary_phone' => '555-0123',
            'secondary_phone' => '555-0124',
            'address' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'NY',
            'postal_code' => '12345',
            'country' => 'USA',
        ]);

        expect($user->display_name)->toBe('John Doe Smith Jr.');
    });

    it('falls back to user name when profile has default full name', function () {
        $user = User::factory()->create(['name' => 'John Doe']);
        $user->profile()->create([
            'full_name' => 'No name provided',
            'primary_phone' => '555-0123',
            'secondary_phone' => '555-0124',
            'address' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'NY',
            'postal_code' => '12345',
            'country' => 'USA',
        ]);

        expect($user->display_name)->toBe('John Doe');
    });
});

describe('Security Edge Cases', function () {
    it('handles null company_id gracefully', function () {
        $user = User::factory()->create(['company_id' => null]);

        expect($user->canAccessCompany($this->company1->id))->toBeFalse()
            ->and($user->accessibleWarehouses()->count())->toBe(0)
            ->and($user->accessibleBranches()->count())->toBe(0);
    });

    it('handles null branch_id gracefully', function () {
        $user = User::factory()->forCompany($this->company1)->create(['branch_id' => null]);

        expect($user->canAccessBranch($this->branch1->id))->toBeFalse();
    });

    it('prevents access to non-existent resources', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        expect($user->canAccessCompany(99999))->toBeFalse()
            ->and($user->canAccessBranch(99999))->toBeFalse();
    });
});
