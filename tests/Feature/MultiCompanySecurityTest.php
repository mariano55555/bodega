<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('Multi-Company Security', function () {
    beforeEach(function () {
        // Create permissions and roles
        $permissions = [
            'view-branches', 'create-branches', 'edit-branches', 'delete-branches',
            'view-warehouses', 'create-warehouses', 'edit-warehouses', 'delete-warehouses',
            'filter-branches-by-company', 'filter-warehouses-by-company',
            'manage-branch-status', 'manage-warehouse-status',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $superAdminRole = Role::create(['name' => 'super-admin']);
        $companyAdminRole = Role::create(['name' => 'company-admin']);
        $branchManagerRole = Role::create(['name' => 'branch-manager']);
        $warehouseManagerRole = Role::create(['name' => 'warehouse-manager']);
        $warehouseOperatorRole = Role::create(['name' => 'warehouse-operator']);

        $superAdminRole->givePermissionTo($permissions);
        $companyAdminRole->givePermissionTo($permissions);
        $branchManagerRole->givePermissionTo([
            'view-branches', 'edit-branches', 'view-warehouses', 'edit-warehouses',
            'filter-branches-by-company', 'filter-warehouses-by-company',
            'manage-branch-status', 'manage-warehouse-status',
        ]);
        $warehouseManagerRole->givePermissionTo([
            'view-branches', 'view-warehouses', 'edit-warehouses',
            'filter-branches-by-company', 'filter-warehouses-by-company', 'manage-warehouse-status',
        ]);
        $warehouseOperatorRole->givePermissionTo([
            'view-branches', 'view-warehouses', 'filter-branches-by-company', 'filter-warehouses-by-company',
        ]);

        // Create test companies and data
        $this->company1 = Company::factory()->create(['name' => 'Company 1']);
        $this->company2 = Company::factory()->create(['name' => 'Company 2']);

        $this->branch1 = Branch::factory()->forCompany($this->company1)->create(['name' => 'Company 1 Branch']);
        $this->branch2 = Branch::factory()->forCompany($this->company2)->create(['name' => 'Company 2 Branch']);

        $this->warehouse1 = Warehouse::factory()->forCompany($this->company1)
            ->state(['branch_id' => $this->branch1->id, 'name' => 'Company 1 Warehouse'])
            ->create();
        $this->warehouse2 = Warehouse::factory()->forCompany($this->company2)
            ->state(['branch_id' => $this->branch2->id, 'name' => 'Company 2 Warehouse'])
            ->create();
    });

    describe('Company Admin Cross-Company Access Prevention', function () {
        it('prevents company admin from accessing other company branches', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            // Can access own company branch
            $response = $this->getJson("/branches/{$this->branch1->slug}");
            $response->assertSuccessful();

            // Cannot access other company branch
            $response = $this->getJson("/branches/{$this->branch2->slug}");
            $response->assertForbidden();
        });

        it('prevents company admin from accessing other company warehouses', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            // Can access own company warehouse
            $response = $this->getJson("/warehouses/{$this->warehouse1->slug}");
            $response->assertSuccessful();

            // Cannot access other company warehouse
            $response = $this->getJson("/warehouses/{$this->warehouse2->slug}");
            $response->assertForbidden();
        });

        it('prevents company admin from modifying other company branches', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            // Can modify own company branch
            $response = $this->putJson("/branches/{$this->branch1->slug}", [
                'name' => 'Updated Branch Name',
            ]);
            $response->assertSuccessful();

            // Cannot modify other company branch
            $response = $this->putJson("/branches/{$this->branch2->slug}", [
                'name' => 'Hacked Branch Name',
            ]);
            $response->assertForbidden();
        });

        it('prevents company admin from modifying other company warehouses', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            // Can modify own company warehouse
            $response = $this->putJson("/warehouses/{$this->warehouse1->slug}", [
                'name' => 'Updated Warehouse Name',
            ]);
            $response->assertSuccessful();

            // Cannot modify other company warehouse
            $response = $this->putJson("/warehouses/{$this->warehouse2->slug}", [
                'name' => 'Hacked Warehouse Name',
            ]);
            $response->assertForbidden();
        });

        it('prevents company admin from deleting other company resources', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            // Cannot delete other company branch
            $response = $this->deleteJson("/branches/{$this->branch2->slug}");
            $response->assertForbidden();

            // Cannot delete other company warehouse
            $response = $this->deleteJson("/warehouses/{$this->warehouse2->slug}");
            $response->assertForbidden();
        });

        it('filters index results to own company only', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            // Branch index should only show company 1 branches
            $response = $this->getJson('/branches');
            $response->assertSuccessful()
                ->assertJsonCount(1, 'data')
                ->assertJsonFragment(['id' => $this->branch1->id])
                ->assertJsonMissing(['id' => $this->branch2->id]);

            // Warehouse index should only show company 1 warehouses
            $response = $this->getJson('/warehouses');
            $response->assertSuccessful()
                ->assertJsonCount(1, 'data')
                ->assertJsonFragment(['id' => $this->warehouse1->id])
                ->assertJsonMissing(['id' => $this->warehouse2->id]);
        });
    });

    describe('Branch Manager Access Control', function () {
        it('restricts branch manager to assigned branch only', function () {
            $branch1Manager = User::factory()->branchManager()
                ->forCompany($this->company1)
                ->forBranch($this->branch1)
                ->create();
            $this->actingAs($branch1Manager);

            // Can access assigned branch
            $response = $this->getJson("/branches/{$this->branch1->slug}");
            $response->assertSuccessful();

            // Cannot access other branch in same company
            $anotherBranch = Branch::factory()->forCompany($this->company1)->create();
            $response = $this->getJson("/branches/{$anotherBranch->slug}");
            $response->assertForbidden();

            // Cannot access branch in different company
            $response = $this->getJson("/branches/{$this->branch2->slug}");
            $response->assertForbidden();
        });

        it('restricts branch manager to warehouses in assigned branch only', function () {
            $branch1Manager = User::factory()->branchManager()
                ->forCompany($this->company1)
                ->forBranch($this->branch1)
                ->create();
            $this->actingAs($branch1Manager);

            // Can access warehouse in assigned branch
            $response = $this->getJson("/warehouses/{$this->warehouse1->slug}");
            $response->assertSuccessful();

            // Cannot access warehouse in different branch
            $response = $this->getJson("/warehouses/{$this->warehouse2->slug}");
            $response->assertForbidden();

            // Cannot access warehouse in different branch of same company
            $anotherBranch = Branch::factory()->forCompany($this->company1)->create();
            $anotherWarehouse = Warehouse::factory()->forCompany($this->company1)
                ->state(['branch_id' => $anotherBranch->id])
                ->create();
            $response = $this->getJson("/warehouses/{$anotherWarehouse->slug}");
            $response->assertForbidden();
        });

        it('prevents branch manager from creating branches', function () {
            $branch1Manager = User::factory()->branchManager()
                ->forCompany($this->company1)
                ->forBranch($this->branch1)
                ->create();
            $this->actingAs($branch1Manager);

            $response = $this->postJson('/branches', [
                'name' => 'Unauthorized Branch',
                'company_id' => $this->company1->id,
                'type' => 'sucursal',
            ]);

            $response->assertForbidden();
        });

        it('allows branch manager to create warehouses in assigned branch', function () {
            $branch1Manager = User::factory()->branchManager()
                ->forCompany($this->company1)
                ->forBranch($this->branch1)
                ->create();
            $this->actingAs($branch1Manager);

            $response = $this->postJson('/warehouses', [
                'name' => 'New Warehouse',
                'company_id' => $this->company1->id,
                'branch_id' => $this->branch1->id,
                'total_capacity' => 1000,
                'capacity_unit' => 'm3',
            ]);

            $response->assertCreated();
        });
    });

    describe('Warehouse Manager Access Control', function () {
        it('restricts warehouse manager to accessible warehouses', function () {
            $warehouseManager = User::factory()->warehouseManager()
                ->forCompany($this->company1)
                ->forBranch($this->branch1)
                ->create();
            $this->actingAs($warehouseManager);

            // Can access warehouse in assigned branch
            $response = $this->getJson("/warehouses/{$this->warehouse1->slug}");
            $response->assertSuccessful();

            // Cannot access warehouse in different company
            $response = $this->getJson("/warehouses/{$this->warehouse2->slug}");
            $response->assertForbidden();
        });

        it('prevents warehouse manager from creating warehouses', function () {
            $warehouseManager = User::factory()->warehouseManager()
                ->forCompany($this->company1)
                ->forBranch($this->branch1)
                ->create();
            $this->actingAs($warehouseManager);

            $response = $this->postJson('/warehouses', [
                'name' => 'Unauthorized Warehouse',
                'company_id' => $this->company1->id,
                'branch_id' => $this->branch1->id,
                'total_capacity' => 1000,
                'capacity_unit' => 'm3',
            ]);

            $response->assertForbidden();
        });

        it('prevents warehouse manager from deleting warehouses', function () {
            $warehouseManager = User::factory()->warehouseManager()
                ->forCompany($this->company1)
                ->forBranch($this->branch1)
                ->create();
            $this->actingAs($warehouseManager);

            $response = $this->deleteJson("/warehouses/{$this->warehouse1->slug}");
            $response->assertForbidden();
        });

        it('allows warehouse manager to update accessible warehouses', function () {
            $warehouseManager = User::factory()->warehouseManager()
                ->forCompany($this->company1)
                ->forBranch($this->branch1)
                ->create();
            $this->actingAs($warehouseManager);

            $response = $this->putJson("/warehouses/{$this->warehouse1->slug}", [
                'name' => 'Updated Warehouse Name',
                'description' => 'Updated by warehouse manager',
            ]);

            $response->assertSuccessful();
        });
    });

    describe('Warehouse Operator Access Control', function () {
        it('restricts warehouse operator to read-only access', function () {
            $warehouseOperator = User::factory()->warehouseOperator()
                ->forCompany($this->company1)
                ->forBranch($this->branch1)
                ->create();
            $this->actingAs($warehouseOperator);

            // Can view warehouse
            $response = $this->getJson("/warehouses/{$this->warehouse1->slug}");
            $response->assertSuccessful();

            // Cannot modify warehouse
            $response = $this->putJson("/warehouses/{$this->warehouse1->slug}", [
                'name' => 'Unauthorized Update',
            ]);
            $response->assertForbidden();

            // Cannot delete warehouse
            $response = $this->deleteJson("/warehouses/{$this->warehouse1->slug}");
            $response->assertForbidden();

            // Cannot create warehouse
            $response = $this->postJson('/warehouses', [
                'name' => 'Unauthorized Warehouse',
                'company_id' => $this->company1->id,
            ]);
            $response->assertForbidden();
        });

        it('prevents warehouse operator from accessing other company data', function () {
            $warehouseOperator = User::factory()->warehouseOperator()
                ->forCompany($this->company1)
                ->forBranch($this->branch1)
                ->create();
            $this->actingAs($warehouseOperator);

            // Cannot access other company warehouse
            $response = $this->getJson("/warehouses/{$this->warehouse2->slug}");
            $response->assertForbidden();

            // Cannot access other company branch
            $response = $this->getJson("/branches/{$this->branch2->slug}");
            $response->assertForbidden();
        });
    });

    describe('Super Admin Full Access', function () {
        it('allows super admin to access all company data', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $this->actingAs($superAdmin);

            // Can access all branches
            $response = $this->getJson("/branches/{$this->branch1->slug}");
            $response->assertSuccessful();

            $response = $this->getJson("/branches/{$this->branch2->slug}");
            $response->assertSuccessful();

            // Can access all warehouses
            $response = $this->getJson("/warehouses/{$this->warehouse1->slug}");
            $response->assertSuccessful();

            $response = $this->getJson("/warehouses/{$this->warehouse2->slug}");
            $response->assertSuccessful();
        });

        it('allows super admin to modify any company data', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $this->actingAs($superAdmin);

            // Can modify branches from any company
            $response = $this->putJson("/branches/{$this->branch1->slug}", [
                'name' => 'Super Admin Updated Branch 1',
            ]);
            $response->assertSuccessful();

            $response = $this->putJson("/branches/{$this->branch2->slug}", [
                'name' => 'Super Admin Updated Branch 2',
            ]);
            $response->assertSuccessful();

            // Can modify warehouses from any company
            $response = $this->putJson("/warehouses/{$this->warehouse1->slug}", [
                'name' => 'Super Admin Updated Warehouse 1',
            ]);
            $response->assertSuccessful();

            $response = $this->putJson("/warehouses/{$this->warehouse2->slug}", [
                'name' => 'Super Admin Updated Warehouse 2',
            ]);
            $response->assertSuccessful();
        });

        it('shows super admin all data in index endpoints', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $this->actingAs($superAdmin);

            // Branch index should show all branches from all companies
            $response = $this->getJson('/branches');
            $response->assertSuccessful()
                ->assertJsonCount(2, 'data')
                ->assertJsonFragment(['id' => $this->branch1->id])
                ->assertJsonFragment(['id' => $this->branch2->id]);

            // Warehouse index should show all warehouses from all companies
            $response = $this->getJson('/warehouses');
            $response->assertSuccessful()
                ->assertJsonCount(2, 'data')
                ->assertJsonFragment(['id' => $this->warehouse1->id])
                ->assertJsonFragment(['id' => $this->warehouse2->id]);
        });
    });

    describe('Data Injection Prevention', function () {
        it('prevents company_id manipulation in branch creation', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            $response = $this->postJson('/branches', [
                'name' => 'Malicious Branch',
                'company_id' => $this->company2->id, // Try to create branch for different company
                'type' => 'sucursal',
            ]);

            if ($response->status() === 201) {
                // If creation succeeds, verify company_id was overridden
                $createdBranch = Branch::latest()->first();
                expect($createdBranch->company_id)->toBe($this->company1->id);
            }
        });

        it('prevents company_id manipulation in warehouse creation', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            $response = $this->postJson('/warehouses', [
                'name' => 'Malicious Warehouse',
                'company_id' => $this->company2->id, // Try to create warehouse for different company
                'branch_id' => $this->branch1->id,
                'total_capacity' => 1000,
                'capacity_unit' => 'm3',
            ]);

            if ($response->status() === 201) {
                // If creation succeeds, verify company_id was overridden
                $createdWarehouse = Warehouse::latest()->first();
                expect($createdWarehouse->company_id)->toBe($this->company1->id);
            }
        });

        it('prevents branch_id manipulation to access other company branches', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            $response = $this->postJson('/warehouses', [
                'name' => 'Cross-Company Warehouse',
                'company_id' => $this->company1->id,
                'branch_id' => $this->branch2->id, // Try to assign to different company's branch
                'total_capacity' => 1000,
                'capacity_unit' => 'm3',
            ]);

            $response->assertUnprocessable()
                ->assertJsonFragment(['message' => 'La sucursal seleccionada no pertenece a su empresa.']);
        });
    });

    describe('Audit Trail Security', function () {
        it('records correct user in audit fields across companies', function () {
            $company1Admin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $company2Admin = User::factory()->companyAdmin()->forCompany($this->company2)->create();

            // Company 1 admin creates branch
            $this->actingAs($company1Admin);
            $response = $this->postJson('/branches', [
                'name' => 'Company 1 New Branch',
                'company_id' => $this->company1->id,
                'type' => 'sucursal',
            ]);
            $response->assertCreated();

            $company1Branch = Branch::latest()->first();
            expect($company1Branch->created_by)->toBe($company1Admin->id);
            expect($company1Branch->company_id)->toBe($this->company1->id);

            // Company 2 admin creates branch
            $this->actingAs($company2Admin);
            $response = $this->postJson('/branches', [
                'name' => 'Company 2 New Branch',
                'company_id' => $this->company2->id,
                'type' => 'sucursal',
            ]);
            $response->assertCreated();

            $company2Branch = Branch::latest()->first();
            expect($company2Branch->created_by)->toBe($company2Admin->id);
            expect($company2Branch->company_id)->toBe($this->company2->id);

            // Verify each admin can only access their created branches
            $this->actingAs($company1Admin);
            $response = $this->getJson("/branches/{$company2Branch->slug}");
            $response->assertForbidden();

            $this->actingAs($company2Admin);
            $response = $this->getJson("/branches/{$company1Branch->slug}");
            $response->assertForbidden();
        });
    });

    describe('Filter Endpoint Security', function () {
        it('prevents filtering by unauthorized company in branches', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            // Can filter by own company
            $response = $this->getJson("/branches/by-company?company_id={$this->company1->id}");
            $response->assertSuccessful();

            // Cannot filter by other company
            $response = $this->getJson("/branches/by-company?company_id={$this->company2->id}");
            $response->assertForbidden();
        });

        it('prevents filtering by unauthorized company in warehouses', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            // Can filter by own company
            $response = $this->getJson("/warehouses/by-company?company_id={$this->company1->id}");
            $response->assertSuccessful();

            // Cannot filter by other company
            $response = $this->getJson("/warehouses/by-company?company_id={$this->company2->id}");
            $response->assertForbidden();
        });

        it('prevents filtering by unauthorized branch in warehouses', function () {
            $companyAdmin = User::factory()->companyAdmin()->forCompany($this->company1)->create();
            $this->actingAs($companyAdmin);

            // Can filter by own company branch
            $response = $this->getJson("/warehouses/by-branch/{$this->branch1->id}");
            $response->assertSuccessful();

            // Cannot filter by other company branch
            $response = $this->getJson("/warehouses/by-branch/{$this->branch2->id}");
            $response->assertNotFound();
        });
    });
});
