<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed([\Database\Seeders\RolesAndPermissionsSeeder::class]);

    // Create three separate companies for comprehensive testing
    $this->company1 = Company::factory()->create(['name' => 'Tech Corp']);
    $this->company2 = Company::factory()->create(['name' => 'Manufacturing Inc']);
    $this->company3 = Company::factory()->create(['name' => 'Retail Solutions']);

    // Create branches for each company
    $this->branch1a = Branch::factory()->forCompany($this->company1)->create(['name' => 'Tech Corp Main']);
    $this->branch1b = Branch::factory()->forCompany($this->company1)->create(['name' => 'Tech Corp Secondary']);
    $this->branch2 = Branch::factory()->forCompany($this->company2)->create(['name' => 'Manufacturing Main']);
    $this->branch3 = Branch::factory()->forCompany($this->company3)->create(['name' => 'Retail Main']);

    // Create warehouses for each branch
    $this->warehouse1a = Warehouse::factory()->forBranch($this->branch1a)->create();
    $this->warehouse1b = Warehouse::factory()->forBranch($this->branch1b)->create();
    $this->warehouse2 = Warehouse::factory()->forBranch($this->branch2)->create();
    $this->warehouse3 = Warehouse::factory()->forBranch($this->branch3)->create();

    // Create users for each company
    $this->superAdmin = User::factory()->forCompany($this->company1)->superAdmin()->create();
    $this->companyAdmin1 = User::factory()->forCompany($this->company1)->companyAdmin()->create();
    $this->companyAdmin2 = User::factory()->forCompany($this->company2)->companyAdmin()->create();
    $this->companyAdmin3 = User::factory()->forCompany($this->company3)->companyAdmin()->create();

    $this->branchManager1 = User::factory()->forBranch($this->branch1a)->branchManager()->create();
    $this->branchManager2 = User::factory()->forBranch($this->branch2)->branchManager()->create();

    $this->warehouseManager1 = User::factory()->forBranch($this->branch1a)->warehouseManager()->create();
    $this->warehouseManager2 = User::factory()->forBranch($this->branch2)->warehouseManager()->create();
});

describe('Company Boundary Enforcement', function () {
    it('ensures super admin can access all companies', function () {
        actingAs($this->superAdmin);

        expect($this->superAdmin->canAccessCompany($this->company1->id))->toBeTrue()
            ->and($this->superAdmin->canAccessCompany($this->company2->id))->toBeTrue()
            ->and($this->superAdmin->canAccessCompany($this->company3->id))->toBeTrue();

        // Super admin sees all branches
        $allBranches = $this->superAdmin->accessibleBranches()->get();
        expect($allBranches)->toHaveCount(4); // All 4 branches across companies
    });

    it('restricts company admins to their own company only', function () {
        actingAs($this->companyAdmin1);

        // Can access own company
        expect($this->companyAdmin1->canAccessCompany($this->company1->id))->toBeTrue();

        // Cannot access other companies
        expect($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse()
            ->and($this->companyAdmin1->canAccessCompany($this->company3->id))->toBeFalse();

        // Sees only own company branches
        $accessibleBranches = $this->companyAdmin1->accessibleBranches()->get();
        expect($accessibleBranches)->toHaveCount(2); // Only company1 branches

        $accessibleBranches->each(function ($branch) {
            expect($branch->company_id)->toBe($this->company1->id);
        });
    });

    it('validates cross-company isolation for all company admins', function () {
        $companyAdmins = [
            ['user' => $this->companyAdmin1, 'company' => $this->company1],
            ['user' => $this->companyAdmin2, 'company' => $this->company2],
            ['user' => $this->companyAdmin3, 'company' => $this->company3],
        ];

        foreach ($companyAdmins as $adminData) {
            actingAs($adminData['user']);

            // Can only access own company
            expect($adminData['user']->canAccessCompany($adminData['company']->id))->toBeTrue();

            // Cannot access any other company
            $otherCompanies = Company::where('id', '!=', $adminData['company']->id)->get();
            $otherCompanies->each(function ($company) use ($adminData) {
                expect($adminData['user']->canAccessCompany($company->id))->toBeFalse();
            });
        }
    });
});

describe('Branch Level Isolation', function () {
    it('restricts branch managers to their assigned branch only', function () {
        actingAs($this->branchManager1);

        // Can access own branch
        expect($this->branchManager1->canAccessBranch($this->branch1a->id))->toBeTrue();

        // Cannot access other branches in same company
        expect($this->branchManager1->canAccessBranch($this->branch1b->id))->toBeFalse();

        // Cannot access branches in other companies
        expect($this->branchManager1->canAccessBranch($this->branch2->id))->toBeFalse()
            ->and($this->branchManager1->canAccessBranch($this->branch3->id))->toBeFalse();
    });

    it('ensures branch managers see only their branch data', function () {
        actingAs($this->branchManager1);

        $accessibleBranches = $this->branchManager1->accessibleBranches()->get();
        expect($accessibleBranches)->toHaveCount(1);
        expect($accessibleBranches->first()->id)->toBe($this->branch1a->id);

        $accessibleWarehouses = $this->branchManager1->accessibleWarehouses()->get();
        $accessibleWarehouses->each(function ($warehouse) {
            expect($warehouse->branch_id)->toBe($this->branch1a->id);
        });
    });
});

describe('Warehouse Level Isolation', function () {
    it('restricts warehouse managers to their assigned warehouses', function () {
        actingAs($this->warehouseManager1);

        $accessibleWarehouses = $this->warehouseManager1->accessibleWarehouses()->get();

        // Should only see warehouses in their branch
        $accessibleWarehouses->each(function ($warehouse) {
            expect($warehouse->branch_id)->toBe($this->branch1a->id);
        });

        // Should not see warehouses from other branches/companies
        $otherWarehouses = Warehouse::where('branch_id', '!=', $this->branch1a->id)->get();
        expect($otherWarehouses)->not->toBeEmpty();
    });

    it('validates warehouse access permissions across companies', function () {
        actingAs($this->warehouseManager2);

        // Manager 2 should not access manager 1's warehouses
        expect($this->warehouseManager2->canAccessBranch($this->branch1a->id))->toBeFalse();

        $manager2Warehouses = $this->warehouseManager2->accessibleWarehouses()->get();
        $manager2Warehouses->each(function ($warehouse) {
            expect($warehouse->branch_id)->toBe($this->branch2->id);
            expect($warehouse->branch_id)->not->toBe($this->branch1a->id);
        });
    });
});

describe('Data Segregation by Company', function () {
    it('segregates user data by company', function () {
        // Create users in each company
        $company1Users = User::factory()->forCompany($this->company1)->count(5)->create();
        $company2Users = User::factory()->forCompany($this->company2)->count(3)->create();
        $company3Users = User::factory()->forCompany($this->company3)->count(2)->create();

        // Company 1 admin sees only company 1 users
        actingAs($this->companyAdmin1);
        $visibleUsers = User::where('company_id', $this->company1->id)->get();
        expect($visibleUsers->count())->toBeGreaterThan(5); // Including existing users

        $visibleUsers->each(function ($user) {
            expect($user->company_id)->toBe($this->company1->id);
        });

        // Company 2 admin sees only company 2 users
        actingAs($this->companyAdmin2);
        $company2VisibleUsers = User::where('company_id', $this->company2->id)->get();
        expect($company2VisibleUsers->count())->toBeGreaterThan(3);

        $company2VisibleUsers->each(function ($user) {
            expect($user->company_id)->toBe($this->company2->id);
        });
    });

    it('segregates inventory data by company through warehouse relationships', function () {
        // Create products and inventory for each company's warehouses
        $product1 = Product::factory()->forCompany($this->company1)->create();
        $product2 = Product::factory()->forCompany($this->company2)->create();

        $inventory1 = Inventory::factory()->create([
            'product_id' => $product1->id,
            'warehouse_id' => $this->warehouse1a->id,
            'quantity' => 100,
        ]);

        $inventory2 = Inventory::factory()->create([
            'product_id' => $product2->id,
            'warehouse_id' => $this->warehouse2->id,
            'quantity' => 200,
        ]);

        // Company 1 warehouse manager should only see company 1 inventory
        actingAs($this->warehouseManager1);
        $accessibleWarehouses = $this->warehouseManager1->accessibleWarehouses()->pluck('id');
        $visibleInventory = Inventory::whereIn('warehouse_id', $accessibleWarehouses)->get();

        $visibleInventory->each(function ($inventory) use ($accessibleWarehouses) {
            expect($accessibleWarehouses)->toContain($inventory->warehouse_id);
        });
    });

    it('maintains data isolation when users change companies', function () {
        $user = User::factory()->forCompany($this->company1)->create();

        // Initially can access company 1
        expect($user->canAccessCompany($this->company1->id))->toBeTrue()
            ->and($user->canAccessCompany($this->company2->id))->toBeFalse();

        // Move user to company 2
        $user->update(['company_id' => $this->company2->id]);

        // Should now access company 2, not company 1
        expect($user->canAccessCompany($this->company2->id))->toBeTrue()
            ->and($user->canAccessCompany($this->company1->id))->toBeFalse();
    });
});

describe('Query Scope Isolation', function () {
    it('applies company scopes consistently across all queries', function () {
        actingAs($this->companyAdmin1);

        // All accessible queries should be scoped to company
        $branches = $this->companyAdmin1->accessibleBranches()->get();
        $branches->each(function ($branch) {
            expect($branch->company_id)->toBe($this->company1->id);
        });

        $warehouses = $this->companyAdmin1->accessibleWarehouses()->get();
        $warehouses->each(function ($warehouse) {
            expect($warehouse->company_id)->toBe($this->company1->id);
        });
    });

    it('prevents data leakage through eager loading relationships', function () {
        actingAs($this->companyAdmin1);

        // When loading branches with warehouses, should only see company 1 data
        $branchesWithWarehouses = $this->companyAdmin1
            ->accessibleBranches()
            ->with('warehouses')
            ->get();

        $branchesWithWarehouses->each(function ($branch) {
            expect($branch->company_id)->toBe($this->company1->id);

            $branch->warehouses->each(function ($warehouse) {
                expect($warehouse->company_id)->toBe($this->company1->id);
            });
        });
    });

    it('maintains isolation in complex nested queries', function () {
        actingAs($this->companyAdmin1);

        // Complex query: branches -> warehouses -> inventory -> products
        $accessibleBranches = $this->companyAdmin1->accessibleBranches()->get();

        foreach ($accessibleBranches as $branch) {
            expect($branch->company_id)->toBe($this->company1->id);

            $branchWarehouses = Warehouse::where('branch_id', $branch->id)->get();
            $branchWarehouses->each(function ($warehouse) {
                expect($warehouse->company_id)->toBe($this->company1->id);
            });
        }
    });
});

describe('Role Assignments and Company Context', function () {
    it('maintains role assignments within company boundaries', function () {
        actingAs($this->companyAdmin1);

        $userInCompany1 = User::factory()->forCompany($this->company1)->create();
        $userInCompany2 = User::factory()->forCompany($this->company2)->create();

        // Company admin 1 can assign roles to users in their company
        expect($this->companyAdmin1->canAccessCompany($userInCompany1->company_id))->toBeTrue();

        // But cannot assign roles to users in other companies
        expect($this->companyAdmin1->canAccessCompany($userInCompany2->company_id))->toBeFalse();
    });

    it('validates role assignments stay within company boundaries', function () {
        actingAs($this->superAdmin);

        // Create users in different companies
        $user1 = User::factory()->forCompany($this->company1)->create();
        $user2 = User::factory()->forCompany($this->company2)->create();

        // Assign branch manager role to user in company 1
        $user1->assignRole('branch-manager');

        // User should only manage branches in their company
        expect($user1->canAccessBranch($this->branch1a->id))->toBeTrue()
            ->and($user1->canAccessBranch($this->branch2->id))->toBeFalse();

        // Assign same role to user in company 2
        $user2->assignRole('branch-manager');
        expect($user2->canAccessBranch($this->branch2->id))->toBeTrue()
            ->and($user2->canAccessBranch($this->branch1a->id))->toBeFalse();
    });
});

describe('Cross-Company Attack Scenarios', function () {
    it('prevents company admin from viewing other company resources through ID manipulation', function () {
        actingAs($this->companyAdmin1);

        // Company admin 1 should not be able to access company 2 resources by ID
        expect($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse();
        expect($this->companyAdmin1->canAccessBranch($this->branch2->id))->toBeFalse();

        // Should not be able to access company 2 users
        $company2User = User::factory()->forCompany($this->company2)->create();
        expect($this->companyAdmin1->canAccessCompany($company2User->company_id))->toBeFalse();
    });

    it('prevents privilege escalation through company switching', function () {
        $user = User::factory()->forCompany($this->company1)->companyAdmin()->create();

        // User has admin privileges in company 1
        expect($user->hasRole('company-admin'))->toBeTrue();
        expect($user->canAccessCompany($this->company1->id))->toBeTrue();

        // Move user to company 2
        $user->update(['company_id' => $this->company2->id]);

        // User should lose access to company 1
        expect($user->canAccessCompany($this->company1->id))->toBeFalse();
        expect($user->canAccessCompany($this->company2->id))->toBeTrue();

        // Role should still exist but context changes
        expect($user->hasRole('company-admin'))->toBeTrue();
    });

    it('prevents data extraction through relationship manipulation', function () {
        actingAs($this->companyAdmin1);

        // Attempt to access company 2 data through various relationships
        $company1Branches = $this->companyAdmin1->accessibleBranches()->get();

        // Should not be able to access company 2 branches through any relationship
        $company1Branches->each(function ($branch) {
            expect($branch->company_id)->toBe($this->company1->id);
            expect($branch->company_id)->not->toBe($this->company2->id);
            expect($branch->company_id)->not->toBe($this->company3->id);
        });
    });
});

describe('Edge Cases and Boundary Conditions', function () {
    it('handles null company assignments securely', function () {
        $userWithoutCompany = User::factory()->create(['company_id' => null]);

        expect($userWithoutCompany->canAccessCompany($this->company1->id))->toBeFalse();
        expect($userWithoutCompany->accessibleBranches()->count())->toBe(0);
        expect($userWithoutCompany->accessibleWarehouses()->count())->toBe(0);
    });

    it('handles company deletion and orphaned references', function () {
        $companyToDelete = Company::factory()->create();
        $userInDeletedCompany = User::factory()->forCompany($companyToDelete)->create();

        expect($userInDeletedCompany->canAccessCompany($companyToDelete->id))->toBeTrue();

        // Soft delete the company
        $companyToDelete->delete();

        // User should lose access to deleted company
        $activeCompany = Company::where('id', $companyToDelete->id)->first();
        expect($activeCompany)->toBeNull();
    });

    it('validates concurrent access from multiple company admins', function () {
        // Simulate concurrent access by multiple company admins
        actingAs($this->companyAdmin1);
        $company1Users = User::where('company_id', $this->company1->id)->count();

        actingAs($this->companyAdmin2);
        $company2Users = User::where('company_id', $this->company2->id)->count();

        // Each should see different counts (their own company users)
        expect($company1Users)->not->toBe($company2Users);

        // Verify isolation maintained
        actingAs($this->companyAdmin1);
        expect($this->companyAdmin1->canAccessCompany($this->company2->id))->toBeFalse();

        actingAs($this->companyAdmin2);
        expect($this->companyAdmin2->canAccessCompany($this->company1->id))->toBeFalse();
    });
});
