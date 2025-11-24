<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\WarehousePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('WarehousePolicy', function () {
    beforeEach(function () {
        // Create permissions
        Permission::create(['name' => 'view-warehouses']);
        Permission::create(['name' => 'create-warehouses']);
        Permission::create(['name' => 'edit-warehouses']);
        Permission::create(['name' => 'delete-warehouses']);
        Permission::create(['name' => 'filter-warehouses-by-company']);
        Permission::create(['name' => 'filter-warehouses-by-branch']);
        Permission::create(['name' => 'view-warehouse-capacity']);
        Permission::create(['name' => 'manage-warehouse-status']);
        Permission::create(['name' => 'view-inventory']);

        // Create roles with permissions
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $companyAdminRole = Role::create(['name' => 'company-admin']);
        $branchManagerRole = Role::create(['name' => 'branch-manager']);
        $warehouseManagerRole = Role::create(['name' => 'warehouse-manager']);
        $warehouseOperatorRole = Role::create(['name' => 'warehouse-operator']);

        $superAdminRole->givePermissionTo([
            'view-warehouses', 'create-warehouses', 'edit-warehouses', 'delete-warehouses',
            'filter-warehouses-by-company', 'filter-warehouses-by-branch',
            'view-warehouse-capacity', 'manage-warehouse-status', 'view-inventory',
        ]);
        $companyAdminRole->givePermissionTo([
            'view-warehouses', 'create-warehouses', 'edit-warehouses', 'delete-warehouses',
            'filter-warehouses-by-company', 'filter-warehouses-by-branch',
            'view-warehouse-capacity', 'manage-warehouse-status', 'view-inventory',
        ]);
        $branchManagerRole->givePermissionTo([
            'view-warehouses', 'create-warehouses', 'edit-warehouses',
            'filter-warehouses-by-company', 'filter-warehouses-by-branch',
            'view-warehouse-capacity', 'manage-warehouse-status', 'view-inventory',
        ]);
        $warehouseManagerRole->givePermissionTo([
            'view-warehouses', 'edit-warehouses', 'filter-warehouses-by-company',
            'filter-warehouses-by-branch', 'view-warehouse-capacity', 'manage-warehouse-status',
            'view-inventory',
        ]);
        $warehouseOperatorRole->givePermissionTo([
            'view-warehouses', 'filter-warehouses-by-company', 'filter-warehouses-by-branch',
            'view-warehouse-capacity', 'view-inventory',
        ]);

        $this->policy = new WarehousePolicy;
        $this->company1 = Company::factory()->create();
        $this->company2 = Company::factory()->create();
        $this->branch1 = Branch::factory()->forCompany($this->company1)->create();
        $this->branch2 = Branch::factory()->forCompany($this->company1)->create();
        $this->otherBranch = Branch::factory()->forCompany($this->company2)->create();
        $this->warehouse1 = Warehouse::factory()->forCompany($this->company1)->state(['branch_id' => $this->branch1->id])->create();
        $this->warehouse2 = Warehouse::factory()->forCompany($this->company1)->state(['branch_id' => $this->branch2->id])->create();
        $this->otherWarehouse = Warehouse::factory()->forCompany($this->company2)->state(['branch_id' => $this->otherBranch->id])->create();
    });

    describe('viewAny', function () {
        it('allows super admin to view any warehouses', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows company admin to view warehouses', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows branch manager to view warehouses', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows warehouse manager to view warehouses', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows warehouse operator to view warehouses', function () {
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
        it('allows super admin to view any warehouse', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->view($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->view($user, $this->otherWarehouse))->toBeTrue();
        });

        it('allows company admin to view warehouses in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->view($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->view($user, $this->warehouse2))->toBeTrue();
            expect($this->policy->view($user, $this->otherWarehouse))->toBeFalse();
        });

        it('allows branch manager to view warehouses in their branch', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->view($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->view($user, $this->warehouse2))->toBeFalse();
            expect($this->policy->view($user, $this->otherWarehouse))->toBeFalse();
        });

        it('allows warehouse manager to view accessible warehouses', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->view($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->view($user, $this->warehouse2))->toBeFalse();
            expect($this->policy->view($user, $this->otherWarehouse))->toBeFalse();
        });

        it('allows warehouse operator to view accessible warehouses', function () {
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->view($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->view($user, $this->warehouse2))->toBeFalse();
            expect($this->policy->view($user, $this->otherWarehouse))->toBeFalse();
        });

        it('denies access to user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->view($user, $this->warehouse1))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows super admin to create warehouses', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows company admin to create warehouses', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->create($user))->toBeTrue();
        });

        it('allows branch manager to create warehouses', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->create($user))->toBeTrue();
        });

        it('denies warehouse manager from creating warehouses', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('denies warehouse operator from creating warehouses', function () {
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

        it('denies branch manager without branch assignment', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->state(['branch_id' => null])->create();

            expect($this->policy->create($user))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows super admin to update any warehouse', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->update($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->update($user, $this->otherWarehouse))->toBeTrue();
        });

        it('allows company admin to update warehouses in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->update($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->update($user, $this->warehouse2))->toBeTrue();
            expect($this->policy->update($user, $this->otherWarehouse))->toBeFalse();
        });

        it('allows branch manager to update warehouses in their branch', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->update($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->update($user, $this->warehouse2))->toBeFalse();
            expect($this->policy->update($user, $this->otherWarehouse))->toBeFalse();
        });

        it('allows warehouse manager to update accessible warehouses', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->update($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->update($user, $this->warehouse2))->toBeFalse();
            expect($this->policy->update($user, $this->otherWarehouse))->toBeFalse();
        });

        it('denies warehouse operator from updating warehouses', function () {
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->update($user, $this->warehouse1))->toBeFalse();
        });

        it('denies user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->update($user, $this->warehouse1))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows super admin to delete any warehouse', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->delete($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->delete($user, $this->otherWarehouse))->toBeTrue();
        });

        it('allows company admin to delete warehouses in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->delete($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->delete($user, $this->warehouse2))->toBeTrue();
            expect($this->policy->delete($user, $this->otherWarehouse))->toBeFalse();
        });

        it('denies branch manager from deleting warehouses', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->delete($user, $this->warehouse1))->toBeFalse();
        });

        it('denies warehouse manager from deleting warehouses', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->delete($user, $this->warehouse1))->toBeFalse();
        });

        it('denies warehouse operator from deleting warehouses', function () {
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->delete($user, $this->warehouse1))->toBeFalse();
        });

        it('denies user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->delete($user, $this->warehouse1))->toBeFalse();
        });
    });

    describe('restore', function () {
        it('allows super admin to restore any warehouse', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->restore($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->restore($user, $this->otherWarehouse))->toBeTrue();
        });

        it('allows company admin to restore warehouses in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->restore($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->restore($user, $this->otherWarehouse))->toBeFalse();
        });

        it('denies other roles from restoring warehouses', function () {
            $branchManager = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();
            $warehouseManager = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->restore($branchManager, $this->warehouse1))->toBeFalse();
            expect($this->policy->restore($warehouseManager, $this->warehouse1))->toBeFalse();
        });
    });

    describe('forceDelete', function () {
        it('allows super admin to force delete warehouses', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->forceDelete($user, $this->warehouse1))->toBeTrue();
        });

        it('denies all other roles from force deleting', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $branchManager = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->forceDelete($companyAdmin, $this->warehouse1))->toBeFalse();
            expect($this->policy->forceDelete($branchManager, $this->warehouse1))->toBeFalse();
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

    describe('filterByBranch', function () {
        it('allows super admin to filter by any branch', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->filterByBranch($user, $this->branch1->id))->toBeTrue();
            expect($this->policy->filterByBranch($user, $this->otherBranch->id))->toBeTrue();
        });

        it('allows company admin to filter by branches in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->filterByBranch($user, $this->branch1->id))->toBeTrue();
            expect($this->policy->filterByBranch($user, $this->branch2->id))->toBeTrue();
            expect($this->policy->filterByBranch($user, $this->otherBranch->id))->toBeFalse();
        });

        it('allows branch manager to filter by their assigned branch', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->filterByBranch($user, $this->branch1->id))->toBeTrue();
            expect($this->policy->filterByBranch($user, $this->branch2->id))->toBeFalse();
        });

        it('prevents filtering by branches from other companies', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->filterByBranch($user, $this->otherBranch->id))->toBeFalse();
        });

        it('denies user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->filterByBranch($user, $this->branch1->id))->toBeFalse();
        });
    });

    describe('viewCapacity', function () {
        it('allows super admin to view capacity of any warehouse', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->viewCapacity($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->viewCapacity($user, $this->otherWarehouse))->toBeTrue();
        });

        it('allows company admin to view capacity of warehouses in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->viewCapacity($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->viewCapacity($user, $this->otherWarehouse))->toBeFalse();
        });

        it('allows branch manager to view capacity of warehouses in their branch', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->viewCapacity($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->viewCapacity($user, $this->warehouse2))->toBeFalse();
        });

        it('allows warehouse manager to view capacity of accessible warehouses', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->viewCapacity($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->viewCapacity($user, $this->warehouse2))->toBeFalse();
        });

        it('allows warehouse operator to view capacity of accessible warehouses', function () {
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->viewCapacity($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->viewCapacity($user, $this->warehouse2))->toBeFalse();
        });

        it('denies user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->viewCapacity($user, $this->warehouse1))->toBeFalse();
        });
    });

    describe('toggleStatus', function () {
        it('allows super admin to toggle status of any warehouse', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->toggleStatus($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->toggleStatus($user, $this->otherWarehouse))->toBeTrue();
        });

        it('allows company admin to toggle status of warehouses in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->toggleStatus($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->toggleStatus($user, $this->otherWarehouse))->toBeFalse();
        });

        it('allows branch manager to toggle status of warehouses in their branch', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->toggleStatus($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->toggleStatus($user, $this->warehouse2))->toBeFalse();
        });

        it('allows warehouse manager to toggle status of accessible warehouses', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->toggleStatus($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->toggleStatus($user, $this->warehouse2))->toBeFalse();
        });

        it('denies warehouse operator from toggling status', function () {
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->toggleStatus($user, $this->warehouse1))->toBeFalse();
        });

        it('denies user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->toggleStatus($user, $this->warehouse1))->toBeFalse();
        });
    });

    describe('manageInventory', function () {
        it('allows super admin to manage inventory of any warehouse', function () {
            $user = User::factory()->superAdmin()->create();

            expect($this->policy->manageInventory($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->manageInventory($user, $this->otherWarehouse))->toBeTrue();
        });

        it('allows company admin to manage inventory of warehouses in their company', function () {
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            expect($this->policy->manageInventory($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->manageInventory($user, $this->otherWarehouse))->toBeFalse();
        });

        it('allows branch manager to manage inventory of warehouses in their branch', function () {
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->manageInventory($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->manageInventory($user, $this->warehouse2))->toBeFalse();
        });

        it('allows warehouse manager to manage inventory of accessible warehouses', function () {
            $user = User::factory()->warehouseManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->manageInventory($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->manageInventory($user, $this->warehouse2))->toBeFalse();
        });

        it('allows warehouse operator to manage inventory of accessible warehouses', function () {
            $user = User::factory()->warehouseOperator()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->manageInventory($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->manageInventory($user, $this->warehouse2))->toBeFalse();
        });

        it('denies user without permission', function () {
            $user = User::factory()->forCompany($this->company1)->create();

            expect($this->policy->manageInventory($user, $this->warehouse1))->toBeFalse();
        });
    });

    describe('edge cases', function () {
        it('handles warehouse without branch assignment', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company1)->state(['branch_id' => null])->create();
            $user = User::factory()->branchManager()->forCompany($this->company1)->forBranch($this->branch1)->create();

            expect($this->policy->view($user, $warehouse))->toBeFalse();
        });

        it('validates warehouse-branch-company consistency', function () {
            // Create warehouse in different company than its branch (inconsistent state)
            $inconsistentWarehouse = Warehouse::factory()->forCompany($this->company2)->state(['branch_id' => $this->branch1->id])->create();
            $user = User::factory()->companyAdmin()->forCompany($this->company1)->create();

            // Should follow company_id on warehouse, not branch
            expect($this->policy->view($user, $inconsistentWarehouse))->toBeFalse();
        });

        it('handles user with multiple roles correctly', function () {
            $user = User::factory()->forCompany($this->company1)->forBranch($this->branch1)->create();
            $user->assignRole(['company-admin', 'branch-manager']);

            // Should use highest privilege (company-admin)
            expect($this->policy->view($user, $this->warehouse1))->toBeTrue();
            expect($this->policy->view($user, $this->warehouse2))->toBeTrue();
            expect($this->policy->create($user))->toBeTrue();
        });
    });
});
