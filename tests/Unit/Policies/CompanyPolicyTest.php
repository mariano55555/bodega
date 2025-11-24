<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Policies\CompanyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('CompanyPolicy', function () {
    beforeEach(function () {
        // Create roles
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'company_admin']);
        Role::create(['name' => 'branch_manager']);
        Role::create(['name' => 'warehouse_manager']);
        Role::create(['name' => 'warehouse_operator']);

        $this->policy = new CompanyPolicy;
        $this->company1 = Company::factory()->create();
        $this->company2 = Company::factory()->create();
    });

    describe('viewAny', function () {
        it('allows super admin to view any companies', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows company admin to view companies', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows branch manager to view companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows warehouse manager to view companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('denies warehouse operator from viewing companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->viewAny($user))->toBeFalse();
        });

        it('denies regular user from viewing companies', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view', function () {
        it('allows super admin to view any company', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->view($user, $this->company1))->toBeTrue();
            expect($this->policy->view($user, $this->company2))->toBeTrue();
        });

        it('allows company admin to view their own company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->view($user, $this->company1))->toBeTrue();
            expect($this->policy->view($user, $this->company2))->toBeFalse();
        });

        it('allows branch manager to view their own company', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->view($user, $this->company1))->toBeTrue();
            expect($this->policy->view($user, $this->company2))->toBeFalse();
        });

        it('allows warehouse manager to view their own company', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->view($user, $this->company1))->toBeTrue();
            expect($this->policy->view($user, $this->company2))->toBeFalse();
        });

        it('allows warehouse operator to view their own company', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->view($user, $this->company1))->toBeTrue();
            expect($this->policy->view($user, $this->company2))->toBeFalse();
        });

        it('denies regular user from viewing companies', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->view($user, $this->company1))->toBeFalse();
            expect($this->policy->view($user, $this->company2))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows super admin to create companies', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies company admin from creating companies', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies branch manager from creating companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies warehouse manager from creating companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies warehouse operator from creating companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies regular user from creating companies', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows super admin to update any company', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->update($user, $this->company1))->toBeTrue();
            expect($this->policy->update($user, $this->company2))->toBeTrue();
        });

        it('allows company admin to update their own company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->update($user, $this->company1))->toBeTrue();
            expect($this->policy->update($user, $this->company2))->toBeFalse();
        });

        it('denies branch manager from updating companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->update($user, $this->company1))->toBeFalse();
            expect($this->policy->update($user, $this->company2))->toBeFalse();
        });

        it('denies warehouse manager from updating companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->update($user, $this->company1))->toBeFalse();
            expect($this->policy->update($user, $this->company2))->toBeFalse();
        });

        it('denies warehouse operator from updating companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->update($user, $this->company1))->toBeFalse();
            expect($this->policy->update($user, $this->company2))->toBeFalse();
        });

        it('denies regular user from updating companies', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->update($user, $this->company1))->toBeFalse();
            expect($this->policy->update($user, $this->company2))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows super admin to delete companies', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->delete($user, $this->company1))->toBeTrue();
            expect($this->policy->delete($user, $this->company2))->toBeTrue();
        });

        it('denies company admin from deleting companies', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->delete($user, $this->company1))->toBeFalse();
            expect($this->policy->delete($user, $this->company2))->toBeFalse();
        });

        it('denies branch manager from deleting companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->delete($user, $this->company1))->toBeFalse();
            expect($this->policy->delete($user, $this->company2))->toBeFalse();
        });

        it('denies warehouse manager from deleting companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->delete($user, $this->company1))->toBeFalse();
            expect($this->policy->delete($user, $this->company2))->toBeFalse();
        });

        it('denies warehouse operator from deleting companies', function () {
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->delete($user, $this->company1))->toBeFalse();
            expect($this->policy->delete($user, $this->company2))->toBeFalse();
        });

        it('denies regular user from deleting companies', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->delete($user, $this->company1))->toBeFalse();
            expect($this->policy->delete($user, $this->company2))->toBeFalse();
        });
    });

    describe('restore', function () {
        it('allows super admin to restore companies', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->restore($user, $this->company1))->toBeTrue();
            expect($this->policy->restore($user, $this->company2))->toBeTrue();
        });

        it('denies all other roles from restoring companies', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $branchManager = User::factory()->branchManager()->forCompany($this->company1)->forBranch($branch)->create();
            $warehouseManager = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->restore($companyAdmin, $this->company1))->toBeFalse();
            expect($this->policy->restore($branchManager, $this->company1))->toBeFalse();
            expect($this->policy->restore($warehouseManager, $this->company1))->toBeFalse();
        });
    });

    describe('forceDelete', function () {
        it('allows super admin to force delete companies', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->forceDelete($user, $this->company1))->toBeTrue();
            expect($this->policy->forceDelete($user, $this->company2))->toBeTrue();
        });

        it('denies all other roles from force deleting companies', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $branchManager = User::factory()->branchManager()->forCompany($this->company1)->forBranch($branch)->create();
            $warehouseManager = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($branch)->create();

            expect($this->policy->forceDelete($companyAdmin, $this->company1))->toBeFalse();
            expect($this->policy->forceDelete($branchManager, $this->company1))->toBeFalse();
            expect($this->policy->forceDelete($warehouseManager, $this->company1))->toBeFalse();
        });
    });

    describe('edge cases', function () {
        it('handles user with no company assignment', function () {
            $user = User::factory()->companyAdmin()->state(['company_id' => null])->create();

            expect($this->policy->view($user, $this->company1))->toBeFalse();
            expect($this->policy->update($user, $this->company1))->toBeFalse();
        });

        it('handles user with multiple roles correctly', function () {
            $user = User::factory()->forCompany($this->company1)->create();
            $user->assignRole(['super_admin', 'company_admin']);

            // Should use highest privilege (super_admin)
            expect($this->policy->view($user, $this->company1))->toBeTrue();
            expect($this->policy->view($user, $this->company2))->toBeTrue();
            expect($this->policy->create($user))->toBeTrue();
            expect($this->policy->delete($user, $this->company1))->toBeTrue();
        });

        it('validates role-based access patterns', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $branch = Branch::factory()->forCompany($this->company1)->create();
            $branchManager = User::factory()->branchManager()->forCompany($this->company1)->forBranch($branch)->create();

            // Hierarchical access control
            expect($this->policy->create($superAdmin))->toBeTrue();
            expect($this->policy->create($companyAdmin))->toBeFalse();
            expect($this->policy->create($branchManager))->toBeFalse();

            expect($this->policy->update($superAdmin, $this->company1))->toBeTrue();
            expect($this->policy->update($companyAdmin, $this->company1))->toBeTrue();
            expect($this->policy->update($branchManager, $this->company1))->toBeFalse();

            expect($this->policy->delete($superAdmin, $this->company1))->toBeTrue();
            expect($this->policy->delete($companyAdmin, $this->company1))->toBeFalse();
            expect($this->policy->delete($branchManager, $this->company1))->toBeFalse();
        });

        it('ensures company isolation for non-super-admin roles', function () {
            $company1Admin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $branch2 = Branch::factory()->forCompany($this->company2)->create();
            $company2BranchManager = User::factory()->branchManager()->forCompany($this->company2)->forBranch($branch2)->create();

            // Cross-company access should be denied
            expect($this->policy->view($company1Admin, $this->company2))->toBeFalse();
            expect($this->policy->view($company2BranchManager, $this->company1))->toBeFalse();
            expect($this->policy->update($company1Admin, $this->company2))->toBeFalse();
            expect($this->policy->update($company2BranchManager, $this->company1))->toBeFalse();
        });
    });
});
