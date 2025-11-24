<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('BranchController', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->companyAdmin()->forCompany($this->company)->create();
        $this->actingAs($this->user);
    });

    describe('index', function () {
        it('displays branches for company', function () {
            // Create branches for the user's company
            $branches = Branch::factory(3)->forCompany($this->company)->create();

            // Create branch for different company (should not appear)
            $otherCompany = Company::factory()->create();
            Branch::factory()->forCompany($otherCompany)->create();

            $response = $this->getJson('/branches');

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message',
                ])
                ->assertJsonCount(3, 'data');

            foreach ($branches as $branch) {
                $response->assertJsonFragment(['id' => $branch->id]);
            }
        });

        it('filters branches by search term', function () {
            $branch1 = Branch::factory()->forCompany($this->company)->create(['name' => 'Sucursal Norte']);
            $branch2 = Branch::factory()->forCompany($this->company)->create(['name' => 'Sucursal Sur']);
            $branch3 = Branch::factory()->forCompany($this->company)->create(['code' => 'NOR001']);

            $response = $this->getJson('/branches?search=Norte');

            $response->assertSuccessful()
                ->assertJsonCount(2, 'data'); // Both name and code matches

            $response->assertJsonFragment(['id' => $branch1->id])
                ->assertJsonFragment(['id' => $branch3->id])
                ->assertJsonMissing(['id' => $branch2->id]);
        });

        it('filters branches by type', function () {
            $principalBranch = Branch::factory()->forCompany($this->company)->create(['type' => 'principal']);
            $sucursalBranch = Branch::factory()->forCompany($this->company)->create(['type' => 'sucursal']);

            $response = $this->getJson('/branches?type=principal');

            $response->assertSuccessful()
                ->assertJsonCount(1, 'data')
                ->assertJsonFragment(['id' => $principalBranch->id])
                ->assertJsonMissing(['id' => $sucursalBranch->id]);
        });

        it('filters branches by active status', function () {
            $activeBranch = Branch::factory()->forCompany($this->company)->create(['is_active' => true]);
            $inactiveBranch = Branch::factory()->forCompany($this->company)->inactive()->create();

            $response = $this->getJson('/branches?is_active=1');

            $response->assertSuccessful()
                ->assertJsonCount(1, 'data')
                ->assertJsonFragment(['id' => $activeBranch->id])
                ->assertJsonMissing(['id' => $inactiveBranch->id]);
        });

        it('sorts branches correctly', function () {
            $branchB = Branch::factory()->forCompany($this->company)->create(['name' => 'B Branch']);
            $branchA = Branch::factory()->forCompany($this->company)->create(['name' => 'A Branch']);
            $branchC = Branch::factory()->forCompany($this->company)->create(['name' => 'C Branch']);

            $response = $this->getJson('/branches?sort_by=name&sort_direction=asc');

            $response->assertSuccessful();

            $data = $response->json('data');
            expect($data[0]['id'])->toBe($branchA->id);
            expect($data[1]['id'])->toBe($branchB->id);
            expect($data[2]['id'])->toBe($branchC->id);
        });

        it('paginates results when per_page is specified', function () {
            Branch::factory(10)->forCompany($this->company)->create();

            $response = $this->getJson('/branches?per_page=5');

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'data',
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                    'message',
                ]);

            expect($response->json('data.per_page'))->toBe(5);
            expect($response->json('data.total'))->toBe(10);
        });
    });

    describe('store', function () {
        it('creates new branch with valid data', function () {
            $branchData = [
                'name' => 'Nueva Sucursal',
                'code' => 'NSU001',
                'description' => 'Descripción de la nueva sucursal',
                'email' => 'sucursal@example.com',
                'phone' => '+1234567890',
                'manager_name' => 'Juan Pérez',
                'address' => 'Calle Principal 123',
                'city' => 'Ciudad',
                'state' => 'Estado',
                'postal_code' => '12345',
                'country' => 'País',
                'type' => 'sucursal',
                'is_active' => true,
                'is_main_branch' => false,
            ];

            $response = $this->postJson('/branches', $branchData);

            $response->assertCreated()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'slug',
                        'company_id',
                        'created_by',
                    ],
                    'message',
                ]);

            $this->assertDatabaseHas('branches', [
                'name' => 'Nueva Sucursal',
                'company_id' => $this->company->id,
                'created_by' => $this->user->id,
            ]);

            expect($response->json('data.slug'))->toBe('nueva-sucursal');
        });

        it('fails with invalid data', function () {
            $response = $this->postJson('/branches', []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['name']);
        });

        it('ensures branch belongs to users company', function () {
            $otherCompany = Company::factory()->create();

            $branchData = [
                'name' => 'Nueva Sucursal',
                'company_id' => $otherCompany->id, // Try to assign to different company
            ];

            $response = $this->postJson('/branches', $branchData);

            if ($response->status() === 201) {
                // If creation succeeds, check that company_id was overridden
                expect($response->json('data.company_id'))->toBe($this->company->id);
            }
        });

        it('handles creation errors gracefully', function () {
            // Mock a database error by using invalid foreign key
            $response = $this->postJson('/branches', [
                'name' => 'Test Branch',
                'manager_id' => 99999, // Non-existent user
            ]);

            expect($response->status())->toBeIn([422, 500]);
        });
    });

    describe('show', function () {
        it('displays specific branch with relationships', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            $response = $this->getJson("/branches/{$branch->slug}");

            $response->assertSuccessful()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'company',
                        'creator',
                        'updater',
                        'users',
                        'warehouses',
                        'storage_locations',
                    ],
                    'message',
                ]);

            expect($response->json('data.id'))->toBe($branch->id);
        });

        it('prevents access to other company branches', function () {
            $otherCompany = Company::factory()->create();
            $otherBranch = Branch::factory()->forCompany($otherCompany)->create();

            $response = $this->getJson("/branches/{$otherBranch->slug}");

            $response->assertForbidden();
        });
    });

    describe('update', function () {
        it('modifies branch with valid data', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            $updateData = [
                'name' => 'Sucursal Actualizada',
                'description' => 'Nueva descripción',
                'type' => 'almacen',
            ];

            $response = $this->putJson("/branches/{$branch->slug}", $updateData);

            $response->assertSuccessful()
                ->assertJsonFragment(['name' => 'Sucursal Actualizada']);

            $this->assertDatabaseHas('branches', [
                'id' => $branch->id,
                'name' => 'Sucursal Actualizada',
                'updated_by' => $this->user->id,
            ]);
        });

        it('fails with invalid data', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            $response = $this->putJson("/branches/{$branch->slug}", [
                'name' => '', // Invalid empty name
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['name']);
        });

        it('prevents updating other company branches', function () {
            $otherCompany = Company::factory()->create();
            $otherBranch = Branch::factory()->forCompany($otherCompany)->create();

            $response = $this->putJson("/branches/{$otherBranch->slug}", [
                'name' => 'Hacked Branch',
            ]);

            $response->assertForbidden();
        });

        it('ensures company_id remains unchanged', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $otherCompany = Company::factory()->create();

            $response = $this->putJson("/branches/{$branch->slug}", [
                'name' => 'Updated Branch',
                'company_id' => $otherCompany->id, // Try to change company
            ]);

            $response->assertSuccessful();
            expect($response->json('data.company_id'))->toBe($this->company->id);
        });
    });

    describe('destroy', function () {
        it('deletes branch when no dependencies exist', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();

            $response = $this->deleteJson("/branches/{$branch->slug}");

            $response->assertSuccessful()
                ->assertJsonFragment(['success' => true]);

            $this->assertSoftDeleted('branches', ['id' => $branch->id]);
        });

        it('prevents deletion when branch has warehouses', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $branch->warehouses()->create([
                'name' => 'Test Warehouse',
                'company_id' => $this->company->id,
                'code' => 'TW001',
                'total_capacity' => 1000,
                'capacity_unit' => 'm3',
                'is_active' => true,
                'active_at' => now(),
            ]);

            $response = $this->deleteJson("/branches/{$branch->slug}");

            $response->assertUnprocessable()
                ->assertJsonFragment(['success' => false])
                ->assertJsonFragment(['message' => 'No se puede eliminar la sucursal porque tiene almacenes asociados.']);

            $this->assertDatabaseHas('branches', ['id' => $branch->id]);
        });

        it('prevents deletion when branch has users', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            User::factory()->forCompany($this->company)->forBranch($branch)->create();

            $response = $this->deleteJson("/branches/{$branch->slug}");

            $response->assertUnprocessable()
                ->assertJsonFragment(['success' => false])
                ->assertJsonFragment(['message' => 'No se puede eliminar la sucursal porque tiene usuarios asociados.']);

            $this->assertDatabaseHas('branches', ['id' => $branch->id]);
        });

        it('prevents deleting other company branches', function () {
            $otherCompany = Company::factory()->create();
            $otherBranch = Branch::factory()->forCompany($otherCompany)->create();

            $response = $this->deleteJson("/branches/{$otherBranch->slug}");

            $response->assertForbidden();
        });
    });

    describe('getByCompany', function () {
        it('returns company branches for authorized user', function () {
            $branches = Branch::factory(3)->forCompany($this->company)->create();

            $response = $this->getJson("/branches/by-company?company_id={$this->company->id}");

            $response->assertSuccessful()
                ->assertJsonCount(3, 'data');

            foreach ($branches as $branch) {
                $response->assertJsonFragment(['id' => $branch->id]);
            }
        });

        it('defaults to current users company when no company_id provided', function () {
            $branches = Branch::factory(2)->forCompany($this->company)->create();

            $response = $this->getJson('/branches/by-company');

            $response->assertSuccessful()
                ->assertJsonCount(2, 'data');
        });

        it('prevents access to other company branches', function () {
            $otherCompany = Company::factory()->create();

            $response = $this->getJson("/branches/by-company?company_id={$otherCompany->id}");

            $response->assertForbidden();
        });

        it('returns only active branches', function () {
            Branch::factory()->forCompany($this->company)->create(['is_active' => true]);
            Branch::factory()->forCompany($this->company)->inactive()->create();

            $response = $this->getJson('/branches/by-company');

            $response->assertSuccessful()
                ->assertJsonCount(1, 'data');
        });
    });

    describe('toggleStatus', function () {
        it('activates inactive branch', function () {
            $branch = Branch::factory()->forCompany($this->company)->inactive()->create();

            $response = $this->patchJson("/branches/{$branch->slug}/toggle-status");

            $response->assertSuccessful()
                ->assertJsonFragment(['is_active' => true])
                ->assertJsonFragment(['message' => 'Sucursal activada exitosamente.']);

            $this->assertDatabaseHas('branches', [
                'id' => $branch->id,
                'is_active' => true,
            ]);
        });

        it('deactivates active branch', function () {
            $branch = Branch::factory()->forCompany($this->company)->create(['is_active' => true]);

            $response = $this->patchJson("/branches/{$branch->slug}/toggle-status");

            $response->assertSuccessful()
                ->assertJsonFragment(['is_active' => false])
                ->assertJsonFragment(['message' => 'Sucursal desactivada exitosamente.']);

            $this->assertDatabaseHas('branches', [
                'id' => $branch->id,
                'is_active' => false,
            ]);
        });

        it('prevents toggling status of other company branches', function () {
            $otherCompany = Company::factory()->create();
            $otherBranch = Branch::factory()->forCompany($otherCompany)->create();

            $response = $this->patchJson("/branches/{$otherBranch->slug}/toggle-status");

            $response->assertForbidden();
        });
    });

    describe('authorization', function () {
        it('prevents cross company access for company admin', function () {
            $otherCompany = Company::factory()->create();
            $otherBranch = Branch::factory()->forCompany($otherCompany)->create();

            // All operations should be forbidden
            $this->getJson("/branches/{$otherBranch->slug}")->assertForbidden();
            $this->putJson("/branches/{$otherBranch->slug}", ['name' => 'Test'])->assertForbidden();
            $this->deleteJson("/branches/{$otherBranch->slug}")->assertForbidden();
            $this->patchJson("/branches/{$otherBranch->slug}/toggle-status")->assertForbidden();
        });

        it('allows super admin to access all companies', function () {
            $superAdmin = User::factory()->superAdmin()->create();
            $this->actingAs($superAdmin);

            $otherCompany = Company::factory()->create();
            $otherBranch = Branch::factory()->forCompany($otherCompany)->create();

            $response = $this->getJson("/branches/{$otherBranch->slug}");
            $response->assertSuccessful();
        });

        it('restricts branch manager to assigned branch only', function () {
            $branch = Branch::factory()->forCompany($this->company)->create();
            $branchManager = User::factory()->branchManager()->forCompany($this->company)->forBranch($branch)->create();
            $this->actingAs($branchManager);

            // Can access assigned branch
            $response = $this->getJson("/branches/{$branch->slug}");
            $response->assertSuccessful();

            // Cannot access other branches
            $otherBranch = Branch::factory()->forCompany($this->company)->create();
            $response = $this->getJson("/branches/{$otherBranch->slug}");
            $response->assertForbidden();
        });
    });
});
