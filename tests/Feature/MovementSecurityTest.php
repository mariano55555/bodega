<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Movement Security and Authorization', function () {
    beforeEach(function () {
        // Company A setup
        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->userA = User::factory()->forCompany($this->companyA)->create();
        $this->managerA = User::factory()->forCompany($this->companyA)->create(); // Add manager role
        $this->warehouseA = Warehouse::factory()->forCompany($this->companyA)->create();
        $this->productA = Product::factory()->forCompany($this->companyA)->create();
        $this->lotA = ProductLot::factory()->create(['product_id' => $this->productA->id]);
        $this->supplierA = Supplier::factory()->forCompany($this->companyA)->create();

        // Company B setup (different company)
        $this->companyB = Company::factory()->create(['name' => 'Company B']);
        $this->userB = User::factory()->forCompany($this->companyB)->create();
        $this->warehouseB = Warehouse::factory()->forCompany($this->companyB)->create();
        $this->productB = Product::factory()->forCompany($this->companyB)->create();
        $this->lotB = ProductLot::factory()->create(['product_id' => $this->productB->id]);

        // Unauthenticated user
        $this->guestUser = User::factory()->make(); // Not persisted
    });

    describe('authentication requirements', function () {
        it('rejects unauthenticated movement creation requests', function () {
            $this->postJson('/api/movements', [
                'movement_type' => 'sale',
                'product_id' => $this->productA->id,
                'warehouse_id' => $this->warehouseA->id,
                'quantity' => -10.0,
                'unit_cost' => 25.00,
            ])->assertUnauthorized();
        });

        it('rejects unauthenticated movement retrieval requests', function () {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->productA->id,
                'warehouse_id' => $this->warehouseA->id,
            ]);

            $this->getJson("/api/movements/{$movement->id}")
                ->assertUnauthorized();
        });

        it('rejects unauthenticated lot creation requests', function () {
            $this->postJson('/api/lots', [
                'product_id' => $this->productA->id,
                'supplier_id' => $this->supplierA->id,
                'manufactured_date' => now()->subDays(5)->format('Y-m-d'),
                'expiration_date' => now()->addDays(365)->format('Y-m-d'),
                'quantity_produced' => 100.0,
                'unit_cost' => 25.00,
            ])->assertUnauthorized();
        });

        it('accepts valid authentication tokens', function () {
            Sanctum::actingAs($this->userA);

            $this->getJson('/api/user')
                ->assertOk()
                ->assertJson(['id' => $this->userA->id]);
        });
    });

    describe('company isolation', function () {
        it('prevents cross-company movement access', function () {
            $movementA = InventoryMovement::factory()->create([
                'product_id' => $this->productA->id,
                'warehouse_id' => $this->warehouseA->id,
            ]);

            // User from Company B should not access Company A's movement
            $this->actingAs($this->userB)
                ->getJson("/api/movements/{$movementA->id}")
                ->assertNotFound(); // Should return 404, not 403, to avoid information disclosure
        });

        it('prevents cross-company lot access', function () {
            // User from Company B should not access Company A's lot
            $this->actingAs($this->userB)
                ->getJson("/api/lots/{$this->lotA->slug}")
                ->assertNotFound();
        });

        it('prevents cross-company product movement creation', function () {
            $this->actingAs($this->userB)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->productA->id, // Company A's product
                    'warehouse_id' => $this->warehouseB->id, // Company B's warehouse
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                ])
                ->assertForbidden();
        });

        it('prevents cross-company warehouse access', function () {
            $this->actingAs($this->userB)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->productB->id,
                    'warehouse_id' => $this->warehouseA->id, // Company A's warehouse
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                ])
                ->assertForbidden();
        });

        it('prevents cross-company transfers', function () {
            $this->actingAs($this->userA)
                ->postJson('/api/movements/transfer', [
                    'product_id' => $this->productA->id,
                    'from_warehouse_id' => $this->warehouseA->id,
                    'to_warehouse_id' => $this->warehouseB->id, // Different company
                    'quantity' => 25.0,
                    'unit_cost' => 25.00,
                ])
                ->assertForbidden();
        });

        it('isolates movement history by company', function () {
            // Create movements for both companies
            InventoryMovement::factory()->count(5)->create([
                'product_id' => $this->productA->id,
                'warehouse_id' => $this->warehouseA->id,
            ]);

            InventoryMovement::factory()->count(3)->create([
                'product_id' => $this->productB->id,
                'warehouse_id' => $this->warehouseB->id,
            ]);

            // Company A user should only see Company A movements
            $responseA = $this->actingAs($this->userA)
                ->getJson('/api/movements')
                ->assertOk();

            expect($responseA->json('data'))->toHaveCount(5);

            // Company B user should only see Company B movements
            $responseB = $this->actingAs($this->userB)
                ->getJson('/api/movements')
                ->assertOk();

            expect($responseB->json('data'))->toHaveCount(3);
        });

        it('isolates lot listings by company', function () {
            // Company A user should only see Company A lots
            $responseA = $this->actingAs($this->userA)
                ->getJson('/api/lots')
                ->assertOk();

            $lotsA = $responseA->json('data');
            expect($lotsA)->toContain(
                fn ($lot) => $lot['product']['company_id'] === $this->companyA->id
            );

            // Company B user should only see Company B lots
            $responseB = $this->actingAs($this->userB)
                ->getJson('/api/lots')
                ->assertOk();

            $lotsB = $responseB->json('data');
            expect($lotsB)->toContain(
                fn ($lot) => $lot['product']['company_id'] === $this->companyB->id
            );
        });
    });

    describe('role-based authorization', function () {
        it('allows warehouse operators to create basic movements', function () {
            $this->actingAs($this->userA)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->productA->id,
                    'product_lot_id' => $this->lotA->id,
                    'warehouse_id' => $this->warehouseA->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                ])
                ->assertCreated();
        });

        it('restricts movement approval to managers only', function () {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->productA->id,
                'warehouse_id' => $this->warehouseA->id,
                'status' => 'pending_approval',
                'requires_approval' => true,
            ]);

            // Regular user cannot approve
            $this->actingAs($this->userA)
                ->patchJson("/api/movements/{$movement->id}/approve")
                ->assertForbidden();

            // Manager can approve
            $this->actingAs($this->managerA)
                ->patchJson("/api/movements/{$movement->id}/approve", [
                    'approval_notes' => 'Approved by manager',
                ])
                ->assertOk();
        });

        it('restricts lot creation to authorized users', function () {
            // Warehouse operator should be able to create lots
            $this->actingAs($this->userA)
                ->postJson('/api/lots', [
                    'product_id' => $this->productA->id,
                    'supplier_id' => $this->supplierA->id,
                    'manufactured_date' => now()->subDays(5)->format('Y-m-d'),
                    'expiration_date' => now()->addDays(365)->format('Y-m-d'),
                    'quantity_produced' => 100.0,
                    'unit_cost' => 25.00,
                ])
                ->assertCreated();
        });

        it('restricts sensitive operations to managers', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->productA->id,
                'status' => 'active',
            ]);

            // Regular user cannot quarantine lots
            $this->actingAs($this->userA)
                ->patchJson("/api/lots/{$lot->slug}/quarantine", [
                    'quarantine_reason' => 'Quality issue',
                ])
                ->assertForbidden();

            // Manager can quarantine lots
            $this->actingAs($this->managerA)
                ->patchJson("/api/lots/{$lot->slug}/quarantine", [
                    'quarantine_reason' => 'Quality issue',
                    'quarantine_notes' => 'Failed quality control',
                ])
                ->assertOk();
        });
    });

    describe('data sanitization and validation', function () {
        it('sanitizes input data to prevent injection attacks', function () {
            $maliciousInput = "<script>alert('xss')</script>";
            $sqlInjection = "'; DROP TABLE inventory_movements; --";

            $this->actingAs($this->userA)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->productA->id,
                    'warehouse_id' => $this->warehouseA->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                    'notes' => $maliciousInput,
                    'reference_number' => $sqlInjection,
                ])
                ->assertCreated();

            $movement = InventoryMovement::latest()->first();
            expect($movement->notes)->not->toContain('<script>');
            expect($movement->reference_number)->not->toContain('DROP TABLE');
        });

        it('validates quantity ranges to prevent manipulation', function () {
            $this->actingAs($this->userA)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->productA->id,
                    'warehouse_id' => $this->warehouseA->id,
                    'quantity' => -999999999, // Extreme value
                    'unit_cost' => 25.00,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['quantity']);
        });

        it('validates cost ranges to prevent financial manipulation', function () {
            $this->actingAs($this->userA)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->productA->id,
                    'warehouse_id' => $this->warehouseA->id,
                    'quantity' => -10.0,
                    'unit_cost' => -100.00, // Negative cost
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['unit_cost']);
        });

        it('prevents mass assignment vulnerabilities', function () {
            $this->actingAs($this->userA)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->productA->id,
                    'warehouse_id' => $this->warehouseA->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                    'status' => 'completed', // Should not be mass assignable
                    'approved_by' => $this->userA->id, // Should not be mass assignable
                    'created_by' => 999, // Should not be mass assignable
                ])
                ->assertCreated();

            $movement = InventoryMovement::latest()->first();
            expect($movement->status)->toBe('pending'); // Default status
            expect($movement->approved_by)->toBeNull();
            expect($movement->created_by)->toBe($this->userA->id); // Set by system
        });
    });
});
