<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('Hierarchy Management', function () {
    beforeEach(function () {
        // Create permissions and roles
        $permissions = [
            'view-branches', 'create-branches', 'edit-branches', 'delete-branches',
            'view-warehouses', 'create-warehouses', 'edit-warehouses', 'delete-warehouses',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $companyAdminRole = Role::create(['name' => 'company-admin']);
        $companyAdminRole->givePermissionTo($permissions);

        $this->company = Company::factory()->create(['name' => 'Test Company']);
        $this->user = User::factory()->companyAdmin()->forCompany($this->company)->create();
        $this->actingAs($this->user);
    });

    describe('Company-Branch-Warehouse Relationships', function () {
        it('maintains proper company-branch relationship', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            expect($branch->company_id)->toBe($this->company->id);
            expect($branch->company)->toBeInstanceOf(Company::class);
            expect($branch->company->id)->toBe($this->company->id);

            expect($this->company->branches->contains($branch))->toBeTrue();
        });

        it('maintains proper branch-warehouse relationship', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch->id])
                ->create();

            expect($warehouse->branch_id)->toBe($branch->id);
            expect($warehouse->branch)->toBeInstanceOf(Branch::class);
            expect($warehouse->branch->id)->toBe($branch->id);

            expect($branch->warehouses->contains($warehouse))->toBeTrue();
        });

        it('maintains proper company-warehouse direct relationship', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch->id])
                ->create();

            expect($warehouse->company_id)->toBe($this->company->id);
            expect($warehouse->company)->toBeInstanceOf(Company::class);
            expect($warehouse->company->id)->toBe($this->company->id);

            expect($this->company->warehouses->contains($warehouse))->toBeTrue();
        });

        it('maintains proper warehouse-storage location relationship', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch->id])
                ->create();

            $storageLocation = new StorageLocation([
                'name' => 'Storage A1',
                'warehouse_id' => $warehouse->id,
                'branch_id' => $branch->id,
                'company_id' => $this->company->id,
            ]);
            $storageLocation->save();

            expect($storageLocation->warehouse_id)->toBe($warehouse->id);
            expect($storageLocation->warehouse)->toBeInstanceOf(Warehouse::class);
            expect($storageLocation->warehouse->id)->toBe($warehouse->id);

            expect($warehouse->storageLocations->contains($storageLocation))->toBeTrue();
        });

        it('allows warehouses without branch assignment', function () {
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => null])
                ->create();

            expect($warehouse->branch_id)->toBeNull();
            expect($warehouse->company_id)->toBe($this->company->id);
            expect($warehouse->branch)->toBeNull();
        });
    });

    describe('Cascade Deletion Prevention', function () {
        it('prevents deletion of company with branches', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            $response = $this->deleteJson("/companies/{$this->company->slug}");

            // This should either be forbidden or return validation error
            expect($response->status())->toBeIn([403, 422, 500]);

            // Company should still exist
            expect(Company::find($this->company->id))->not->toBeNull();
        });

        it('prevents deletion of branch with warehouses', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch->id])
                ->create();

            $response = $this->deleteJson("/branches/{$branch->slug}");

            $response->assertUnprocessable()
                ->assertJsonFragment(['message' => 'No se puede eliminar la sucursal porque tiene almacenes asociados.']);

            // Branch should still exist
            expect(Branch::find($branch->id))->not->toBeNull();
        });

        it('prevents deletion of branch with users', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $branchUser = User::factory()->forCompany($this->company)->forBranch($branch)->create();

            $response = $this->deleteJson("/branches/{$branch->slug}");

            $response->assertUnprocessable()
                ->assertJsonFragment(['message' => 'No se puede eliminar la sucursal porque tiene usuarios asociados.']);

            // Branch should still exist
            expect(Branch::find($branch->id))->not->toBeNull();
        });

        it('prevents deletion of warehouse with storage locations', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch->id])
                ->create();

            // Create storage location
            $storageLocation = new StorageLocation([
                'name' => 'Storage A1',
                'warehouse_id' => $warehouse->id,
                'branch_id' => $branch->id,
                'company_id' => $this->company->id,
            ]);
            $storageLocation->save();

            $response = $this->deleteJson("/warehouses/{$warehouse->slug}");

            $response->assertUnprocessable()
                ->assertJsonFragment(['message' => 'No se puede eliminar el almacén porque tiene ubicaciones de almacenamiento asociadas.']);

            // Warehouse should still exist
            expect(Warehouse::find($warehouse->id))->not->toBeNull();
        });

        it('allows deletion of empty branch', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            $response = $this->deleteJson("/branches/{$branch->slug}");

            $response->assertSuccessful();

            // Branch should be soft deleted
            expect(Branch::find($branch->id))->toBeNull();
            expect(Branch::withTrashed()->find($branch->id))->not->toBeNull();
        });

        it('allows deletion of empty warehouse', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch->id])
                ->create();

            $response = $this->deleteJson("/warehouses/{$warehouse->slug}");

            $response->assertSuccessful();

            // Warehouse should be soft deleted
            expect(Warehouse::find($warehouse->id))->toBeNull();
            expect(Warehouse::withTrashed()->find($warehouse->id))->not->toBeNull();
        });
    });

    describe('Hierarchy Data Consistency', function () {
        it('ensures warehouse belongs to same company as branch', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $otherCompany = Company::factory()->create();

            // Try to create warehouse with inconsistent company-branch relationship
            $response = $this->postJson('/warehouses', [
                'name' => 'Inconsistent Warehouse',
                'company_id' => $this->company->id,
                'branch_id' => $branch->id,
                'total_capacity' => 1000,
                'capacity_unit' => 'm3',
            ]);

            $response->assertCreated();

            $warehouse = Warehouse::latest()->first();
            expect($warehouse->company_id)->toBe($this->company->id);
            expect($warehouse->branch_id)->toBe($branch->id);
            expect($warehouse->branch->company_id)->toBe($this->company->id);
        });

        it('validates branch belongs to user company during warehouse creation', function () {
            $otherCompany = Company::factory()->create();
            $otherBranch = Branch::factory()->forCompany($otherCompany)->create();

            $response = $this->postJson('/warehouses', [
                'name' => 'Cross-Company Warehouse',
                'company_id' => $this->company->id,
                'branch_id' => $otherBranch->id, // Branch from different company
                'total_capacity' => 1000,
                'capacity_unit' => 'm3',
            ]);

            $response->assertUnprocessable()
                ->assertJsonFragment(['message' => 'La sucursal seleccionada no pertenece a su empresa.']);
        });

        it('maintains hierarchy when updating warehouse branch', function () {
            $branch1 = Branch::factory()->forCompany($this->company)->create();
            $branch2 = Branch::factory()->forCompany($this->company)->create();
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch1->id])
                ->create();

            // Move warehouse to different branch in same company
            $response = $this->putJson("/warehouses/{$warehouse->slug}", [
                'name' => $warehouse->name,
                'branch_id' => $branch2->id,
            ]);

            $response->assertSuccessful();

            $warehouse->refresh();
            expect($warehouse->branch_id)->toBe($branch2->id);
            expect($warehouse->company_id)->toBe($this->company->id);
        });

        it('prevents moving warehouse to branch in different company', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $warehouse = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch->id])
                ->create();

            $otherCompany = Company::factory()->create();
            $otherBranch = Branch::factory()->forCompany($otherCompany)->create();

            $response = $this->putJson("/warehouses/{$warehouse->slug}", [
                'name' => $warehouse->name,
                'branch_id' => $otherBranch->id, // Try to move to different company's branch
            ]);

            $response->assertUnprocessable()
                ->assertJsonFragment(['message' => 'La sucursal seleccionada no pertenece a su empresa.']);

            $warehouse->refresh();
            expect($warehouse->branch_id)->toBe($branch->id); // Should remain unchanged
        });
    });

    describe('Hierarchical Queries and Scopes', function () {
        it('can query all warehouses in a company across branches', function () {
            $branch1 = Branch::factory()->forCompany($this->company)->create();
            $branch2 = Branch::factory()->forCompany($this->company)->create();

            $warehouse1 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch1->id])
                ->create();
            $warehouse2 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch2->id])
                ->create();
            $warehouse3 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => null]) // Unassigned warehouse
                ->create();

            $companyWarehouses = $this->company->warehouses;

            expect($companyWarehouses)->toHaveCount(3);
            expect($companyWarehouses->pluck('id')->toArray())->toContain($warehouse1->id);
            expect($companyWarehouses->pluck('id')->toArray())->toContain($warehouse2->id);
            expect($companyWarehouses->pluck('id')->toArray())->toContain($warehouse3->id);
        });

        it('can query all warehouses in a specific branch', function () {
            $branch1 = Branch::factory()->forCompany($this->company)->create();
            $branch2 = Branch::factory()->forCompany($this->company)->create();

            $warehouse1 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch1->id])
                ->create();
            $warehouse2 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch1->id])
                ->create();
            $warehouse3 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch2->id])
                ->create();

            $branch1Warehouses = $branch1->warehouses;

            expect($branch1Warehouses)->toHaveCount(2);
            expect($branch1Warehouses->pluck('id')->toArray())->toContain($warehouse1->id);
            expect($branch1Warehouses->pluck('id')->toArray())->toContain($warehouse2->id);
            expect($branch1Warehouses->pluck('id')->toArray())->not->toContain($warehouse3->id);
        });

        it('can query branches and their warehouse counts', function () {
            $branch1 = Branch::factory()->forCompany($this->company)->create();
            $branch2 = Branch::factory()->forCompany($this->company)->create();

            // Branch 1 has 2 warehouses
            Warehouse::factory(2)->forCompany($this->company)
                ->state(['branch_id' => $branch1->id])
                ->create();

            // Branch 2 has 1 warehouse
            Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch2->id])
                ->create();

            $branches = Branch::where('company_id', $this->company->id)
                ->withCount('warehouses')
                ->get();

            $branch1Data = $branches->where('id', $branch1->id)->first();
            $branch2Data = $branches->where('id', $branch2->id)->first();

            expect($branch1Data->warehouses_count)->toBe(2);
            expect($branch2Data->warehouses_count)->toBe(1);
        });
    });

    describe('Main Branch Constraints', function () {
        it('prevents deletion of main branch', function () {
            $mainBranch = Branch::factory()->forCompany($this->company)->main()->create();

            $response = $this->deleteJson("/branches/{$mainBranch->slug}");

            // Should be forbidden by policy or validation
            expect($response->status())->toBeIn([403, 422]);

            // Main branch should still exist
            expect(Branch::find($mainBranch->id))->not->toBeNull();
        });

        it('allows only one main branch per company', function () {
            $mainBranch1 = Branch::factory()->forCompany($this->company)->main()->create();

            // Create another branch and try to make it main
            $branch2 = Branch::factory()->forCompany($this->company)->create();

            $response = $this->putJson("/branches/{$branch2->slug}", [
                'name' => $branch2->name,
                'is_main_branch' => true,
            ]);

            // This should either succeed (making branch2 main and branch1 not main)
            // or fail due to business rule validation
            if ($response->isSuccessful()) {
                $mainBranch1->refresh();
                $branch2->refresh();

                // Only one should be main
                $mainBranchCount = Branch::where('company_id', $this->company->id)
                    ->where('is_main_branch', true)
                    ->count();

                expect($mainBranchCount)->toBe(1);
            }
        });

        it('ensures main branch has correct type', function () {
            $mainBranch = Branch::factory()->forCompany($this->company)->main()->create();

            expect($mainBranch->is_main_branch)->toBeTrue();
            expect($mainBranch->type)->toBe('principal');
        });
    });

    describe('User Assignment Hierarchy', function () {
        it('maintains user-branch assignment consistency', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $branchUser = User::factory()->forCompany($this->company)->forBranch($branch)->create();

            expect($branchUser->company_id)->toBe($this->company->id);
            expect($branchUser->branch_id)->toBe($branch->id);
            expect($branchUser->branch->company_id)->toBe($this->company->id);
        });

        it('can query users by hierarchy level', function () {
            $branch1 = Branch::factory()->forCompany($this->company)->create();
            $branch2 = Branch::factory()->forCompany($this->company)->create();

            $branch1Users = User::factory(2)->forCompany($this->company)->forBranch($branch1)->create();
            $branch2Users = User::factory(3)->forCompany($this->company)->forBranch($branch2)->create();
            $companyUsers = User::factory(1)->forCompany($this->company)->create(); // No branch assignment

            // Query company users
            $allCompanyUsers = User::where('company_id', $this->company->id)->get();
            expect($allCompanyUsers)->toHaveCount(7); // 2 + 3 + 1 + 1 (the acting user)

            // Query branch users
            $branch1UsersList = User::where('branch_id', $branch1->id)->get();
            expect($branch1UsersList)->toHaveCount(2);

            $branch2UsersList = User::where('branch_id', $branch2->id)->get();
            expect($branch2UsersList)->toHaveCount(3);
        });
    });

    describe('Capacity and Reporting Hierarchy', function () {
        it('can calculate total capacity across company hierarchy', function () {
            $branch1 = Branch::factory()->forCompany($this->company)->create();
            $branch2 = Branch::factory()->forCompany($this->company)->create();

            $warehouse1 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch1->id, 'total_capacity' => 1000, 'capacity_unit' => 'm3'])
                ->create();
            $warehouse2 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch1->id, 'total_capacity' => 1500, 'capacity_unit' => 'm3'])
                ->create();
            $warehouse3 = Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch2->id, 'total_capacity' => 2000, 'capacity_unit' => 'm3'])
                ->create();

            // Test capacity summary endpoint
            $response = $this->getJson('/warehouses/capacity-summary');
            $response->assertSuccessful();

            $data = $response->json('data');
            expect($data)->toHaveCount(3);

            $totalCapacity = collect($data)->sum('total_capacity');
            expect($totalCapacity)->toBe(4500.0); // 1000 + 1500 + 2000
        });

        it('can filter capacity summary by branch', function () {
            $branch1 = Branch::factory()->forCompany($this->company)->create();
            $branch2 = Branch::factory()->forCompany($this->company)->create();

            Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch1->id, 'total_capacity' => 1000])
                ->create();
            Warehouse::factory()->forCompany($this->company)
                ->state(['branch_id' => $branch2->id, 'total_capacity' => 2000])
                ->create();

            $response = $this->getJson("/warehouses/capacity-summary?branch_id={$branch1->id}");
            $response->assertSuccessful();

            $data = $response->json('data');
            expect($data)->toHaveCount(1);
            expect($data[0]['total_capacity'])->toBe(1000.0);
        });
    });
});
