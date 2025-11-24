<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Policies\BranchPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('BranchPolicy', function () {
    beforeEach(function () {
        // Create permissions
        Permission::create(['name' => 'view-branches']);
        Permission::create(['name' => 'create-branches']);
        Permission::create(['name' => 'edit-branches']);
        Permission::create(['name' => 'delete-branches']);
        Permission::create(['name' => 'filter-branches-by-company']);
        Permission::create(['name' => 'manage-branch-status']);

        // Create roles with permissions
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $companyAdminRole = Role::create(['name' => 'company-admin']);
        $branchManagerRole = Role::create(['name' => 'branch-manager']);
        $warehouseManagerRole = Role::create(['name' => 'warehouse-manager']);
        $warehouseOperatorRole = Role::create(['name' => 'warehouse-operator']);

        $superAdminRole->givePermissionTo([
            'view-branches', 'create-branches', 'edit-branches', 'delete-branches',
            'filter-branches-by-company', 'manage-branch-status',
        ]);
        $companyAdminRole->givePermissionTo([
            'view-branches', 'create-branches', 'edit-branches', 'delete-branches',
            'filter-branches-by-company', 'manage-branch-status',
        ]);
        $branchManagerRole->givePermissionTo([
            'view-branches', 'edit-branches', 'filter-branches-by-company', 'manage-branch-status',
        ]);
        $warehouseManagerRole->givePermissionTo(['view-branches', 'filter-branches-by-company']);
        $warehouseOperatorRole->givePermissionTo(['view-branches', 'filter-branches-by-company']);

        $this->policy = new BranchPolicy;
        $this->company1 = Company::factory()->create();
        $this->company2 = Company::factory()->create();
        $this->branch1 = Branch::factory()->forCompany($this->company1)->create();
        $this->branch2 = Branch::factory()->forCompany($this->company2)->create();
        $this->mainBranch = Branch::factory()->forCompany($this->company1)->main()->create();
    });

    describe('viewAny', function () {
        it('allows super admin to view any branches', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows company admin to view branches', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows branch manager to view branches', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows warehouse manager to view branches', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows warehouse operator to view branches', function () {
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('denies access to user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->viewAny($user))->toBeFalse();
        });

        it('denies access to user without company', function () {
            $user = User::factory()->superAdmin()->state(['company_id' => null])->create();

            expect($this->policy->viewAny($user))->toBeFalse();
        });
    });

    describe('view', function () {
        it('allows super admin to view any branch', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->view($user, $this->branch1))->toBeTrue();
            expect($this->policy->view($user, $this->branch2))->toBeTrue();
        });

        it('allows company admin to view branches in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->view($user, $this->branch1))->toBeTrue();
            expect($this->policy->view($user, $this->branch2))->toBeFalse();
        });

        it('allows branch manager to view only their assigned branch', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->view($user, $this->branch1))->toBeTrue();
            expect($this->policy->view($user, $this->mainBranch))->toBeFalse();
        });

        it('allows warehouse manager to view branches with accessible warehouses', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->view($user, $this->branch1))->toBeTrue();
            expect($this->policy->view($user, $this->branch2))->toBeFalse();
        });

        it('allows warehouse operator to view branches with accessible warehouses', function () {
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->view($user, $this->branch1))->toBeTrue();
            expect($this->policy->view($user, $this->branch2))->toBeFalse();
        });

        it('denies access to user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->view($user, $this->branch1))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows super admin to create branches', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows company admin to create branches', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies branch manager from creating branches', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies warehouse manager from creating branches', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies warehouse operator from creating branches', function () {
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies user without company', function () {
            $user = User::factory()->superAdmin()->state(['company_id' => null])->create();

            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows super admin to update any branch', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->update($user, $this->branch1))->toBeTrue();
            expect($this->policy->update($user, $this->branch2))->toBeTrue();
        });

        it('allows company admin to update branches in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->update($user, $this->branch1))->toBeTrue();
            expect($this->policy->update($user, $this->branch2))->toBeFalse();
        });

        it('allows branch manager to update their assigned branch', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->update($user, $this->branch1))->toBeTrue();
            expect($this->policy->update($user, $this->mainBranch))->toBeFalse();
        });

        it('denies warehouse manager from updating branches', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->update($user, $this->branch1))->toBeFalse();
        });

        it('denies warehouse operator from updating branches', function () {
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->update($user, $this->branch1))->toBeFalse();
        });

        it('denies user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->update($user, $this->branch1))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows super admin to delete non-main branches', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->delete($user, $this->branch1))->toBeTrue();
            expect($this->policy->delete($user, $this->branch2))->toBeTrue();
        });

        it('prevents super admin from deleting main branches', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->delete($user, $this->mainBranch))->toBeFalse();
        });

        it('allows company admin to delete non-main branches in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->delete($user, $this->branch1))->toBeTrue();
            expect($this->policy->delete($user, $this->branch2))->toBeFalse();
        });

        it('prevents company admin from deleting main branches', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->delete($user, $this->mainBranch))->toBeFalse();
        });

        it('denies branch manager from deleting branches', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->delete($user, $this->branch1))->toBeFalse();
        });

        it('denies warehouse manager from deleting branches', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->delete($user, $this->branch1))->toBeFalse();
        });

        it('denies warehouse operator from deleting branches', function () {
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->delete($user, $this->branch1))->toBeFalse();
        });

        it('denies user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->delete($user, $this->branch1))->toBeFalse();
        });
    });

    describe('restore', function () {
        it('allows super admin to restore any branch', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->restore($user, $this->branch1))->toBeTrue();
            expect($this->policy->restore($user, $this->branch2))->toBeTrue();
        });

        it('allows company admin to restore branches in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->restore($user, $this->branch1))->toBeTrue();
            expect($this->policy->restore($user, $this->branch2))->toBeFalse();
        });

        it('denies other roles from restoring branches', function () {
            $branchManager = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();
            $warehouseManager = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->restore($branchManager, $this->branch1))->toBeFalse();
            expect($this->policy->restore($warehouseManager, $this->branch1))->toBeFalse();
        });
    });

    describe('forceDelete', function () {
        it('allows super admin to force delete non-main branches', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->forceDelete($user, $this->branch1))->toBeTrue();
        });

        it('prevents super admin from force deleting main branches', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->forceDelete($user, $this->mainBranch))->toBeFalse();
        });

        it('denies all other roles from force deleting', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $branchManager = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->forceDelete($companyAdmin, $this->branch1))->toBeFalse();
            expect($this->policy->forceDelete($branchManager, $this->branch1))->toBeFalse();
        });
    });

    describe('filterByCompany', function () {
        it('allows super admin to filter by any company', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->filterByCompany($user, $this->company1->id))->toBeTrue();
            expect($this->policy->filterByCompany($user, $this->company2->id))->toBeTrue();
        });

        it('allows users to filter by their own company only', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->filterByCompany($user, $this->company1->id))->toBeTrue();
            expect($this->policy->filterByCompany($user, $this->company2->id))->toBeFalse();
        });

        it('denies user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->filterByCompany($user, $this->company1->id))->toBeFalse();
        });
    });

    describe('toggleStatus', function () {
        it('allows super admin to toggle status of any branch', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->toggleStatus($user, $this->branch1))->toBeTrue();
            expect($this->policy->toggleStatus($user, $this->branch2))->toBeTrue();
        });

        it('allows company admin to toggle status of branches in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->toggleStatus($user, $this->branch1))->toBeTrue();
            expect($this->policy->toggleStatus($user, $this->branch2))->toBeFalse();
        });

        it('allows branch manager to toggle status of their assigned branch', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->toggleStatus($user, $this->branch1))->toBeTrue();
            expect($this->policy->toggleStatus($user, $this->mainBranch))->toBeFalse();
        });

        it('denies warehouse roles from toggling branch status', function () {
            $warehouseManager = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();
            $warehouseOperator = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->toggleStatus($warehouseManager, $this->branch1))->toBeFalse();
            expect($this->policy->toggleStatus($warehouseOperator, $this->branch1))->toBeFalse();
        });

        it('denies user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->toggleStatus($user, $this->branch1))->toBeFalse();
        });
    });

    describe('edge cases', function () {
        it('handles null user gracefully', function () {
            expect($this->policy->viewAny(null))->toBeFalse();
        });

        it('handles user with multiple roles correctly', function () {
            $user = User::factory()->forCompany($this->company1)->create();
            $user->assignRole(['company-admin', 'branch-manager']);

            // Should use highest privilege (company-admin)
            expect($this->policy->view($user, $this->branch1))->toBeTrue();
            expect($this->policy->create($user))->toBeTrue();
        });

        it('validates branch belongs to company for branch managers', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            // Can view their own branch
            expect($this->policy->view($user, $this->branch1))->toBeTrue();

            // Cannot view other branch in same company
            $otherBranch = Branch::factory()->forCompany($this->company1)->create();
            expect($this->policy->view($user, $otherBranch))->toBeFalse();
        });
    });
});
