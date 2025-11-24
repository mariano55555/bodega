<?php

declare(strict_types=1);

use App\Events\LotCreated;
use App\Jobs\ProcessExpiryMovement;
use App\Jobs\SendExpirationAlerts;
use App\Jobs\UpdateExpiredLots;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('Lot Tracking and Expiration Management', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company)->create();
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create();
        $this->supplier = Supplier::factory()->forCompany($this->company)->create();
    });

    describe('lot creation and tracking', function () {
        beforeEach(function () {
            Event::fake();
        });

        it('creates lot during goods receipt with complete tracking information', function () {
            $this->actingAs($this->user)
                ->postJson('/api/lots', [
                    'lot_number' => 'LOT-TEST-2024-001',
                    'product_id' => $this->product->id,
                    'supplier_id' => $this->supplier->id,
                    'manufactured_date' => now()->subDays(5)->format('Y-m-d'),
                    'expiration_date' => now()->addDays(365)->format('Y-m-d'),
                    'quantity_produced' => 500.0,
                    'unit_cost' => 45.75,
                    'batch_certificate' => 'BC-2024-001',
                    'quality_attributes' => [
                        'moisture_content' => '5.2%',
                        'purity' => '99.8%',
                        'ph_level' => '7.1',
                    ],
                    'notes' => 'High quality batch from premium supplier',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('product_lots', [
                'lot_number' => 'LOT-TEST-2024-001',
                'product_id' => $this->product->id,
                'supplier_id' => $this->supplier->id,
                'quantity_produced' => 500.0,
                'quantity_remaining' => 500.0,
                'status' => 'active',
                'is_active' => true,
            ]);

            Event::assertDispatched(LotCreated::class);
        });

        it('auto-generates lot number when not provided', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/lots', [
                    'product_id' => $this->product->id,
                    'supplier_id' => $this->supplier->id,
                    'manufactured_date' => now()->subDays(5)->format('Y-m-d'),
                    'expiration_date' => now()->addDays(365)->format('Y-m-d'),
                    'quantity_produced' => 100.0,
                    'unit_cost' => 25.00,
                ])
                ->assertCreated();

            $lotData = $response->json('data');
            expect($lotData['lot_number'])->toMatch('/LOT-\d{2}[A-Z]{2}-\d{4}/');
            expect($lotData['slug'])->not->toBeNull();
        });

        it('validates lot number uniqueness per product', function () {
            ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'LOT-DUPLICATE',
            ]);

            $this->actingAs($this->user)
                ->postJson('/api/lots', [
                    'lot_number' => 'LOT-DUPLICATE',
                    'product_id' => $this->product->id,
                    'supplier_id' => $this->supplier->id,
                    'manufactured_date' => now()->subDays(5)->format('Y-m-d'),
                    'expiration_date' => now()->addDays(365)->format('Y-m-d'),
                    'quantity_produced' => 100.0,
                    'unit_cost' => 25.00,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['lot_number' => 'Número de lote ya existe para este producto']);
        });

        it('validates expiration date is after manufactured date', function () {
            $this->actingAs($this->user)
                ->postJson('/api/lots', [
                    'lot_number' => 'LOT-INVALID-DATE',
                    'product_id' => $this->product->id,
                    'supplier_id' => $this->supplier->id,
                    'manufactured_date' => now()->format('Y-m-d'),
                    'expiration_date' => now()->subDays(1)->format('Y-m-d'), // Before manufactured
                    'quantity_produced' => 100.0,
                    'unit_cost' => 25.00,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['expiration_date' => 'Fecha de vencimiento debe ser posterior a fecha de fabricación']);
        });

        it('tracks lot genealogy and traceability', function () {
            $parentLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'PARENT-LOT-001',
            ]);

            $this->actingAs($this->user)
                ->postJson('/api/lots', [
                    'lot_number' => 'CHILD-LOT-001',
                    'product_id' => $this->product->id,
                    'supplier_id' => $this->supplier->id,
                    'manufactured_date' => now()->subDays(3)->format('Y-m-d'),
                    'expiration_date' => now()->addDays(90)->format('Y-m-d'),
                    'quantity_produced' => 50.0,
                    'unit_cost' => 30.00,
                    'parent_lot_id' => $parentLot->id,
                    'notes' => 'Reprocessed from parent lot',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('product_lots', [
                'lot_number' => 'CHILD-LOT-001',
                'parent_lot_id' => $parentLot->id,
            ]);
        });
    });

    describe('expiration monitoring and alerts', function () {
        beforeEach(function () {
            Event::fake();
            Queue::fake();

            // Create lots with different expiration dates
            $this->expiredLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'EXPIRED-LOT',
                'expiration_date' => now()->subDays(1),
                'status' => 'active',
                'quantity_remaining' => 25.0,
            ]);

            $this->expiringSoonLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'EXPIRING-SOON',
                'expiration_date' => now()->addDays(7),
                'status' => 'active',
                'quantity_remaining' => 50.0,
            ]);

            $this->stableLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'STABLE-LOT',
                'expiration_date' => now()->addDays(90),
                'status' => 'active',
                'quantity_remaining' => 100.0,
            ]);
        });

        it('identifies and processes expired lots automatically', function () {
            Artisan::call('lots:check-expiration');

            Queue::assertPushed(UpdateExpiredLots::class);
            Queue::assertPushed(SendExpirationAlerts::class);
        });

        it('updates expired lot status automatically', function () {
            Artisan::call('lots:update-expired');

            $this->expiredLot->refresh();
            expect($this->expiredLot->status)->toBe('expired');
            expect($this->expiredLot->is_active)->toBeFalse();
        });

        it('generates expiration alerts for approaching dates', function () {
            Artisan::call('lots:check-expiration', ['--days' => 30]);

            Queue::assertPushed(SendExpirationAlerts::class, function ($job) {
                return $job->lots->contains($this->expiringSoonLot);
            });
        });

        it('prevents movements from expired lots', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->expiredLot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['product_lot_id' => 'No se puede mover producto de lote vencido']);
        });

        it('allows disposal movements for expired lots', function () {
            $this->expiredLot->update(['status' => 'expired']);

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'expiry',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->expiredLot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -25.0, // Full disposal
                    'unit_cost' => 0.00,
                    'notes' => 'Disposal of expired products',
                    'disposal_method' => 'incineration',
                    'disposal_certificate' => 'DISP-2024-001',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('inventory_movements', [
                'movement_type' => 'expiry',
                'product_lot_id' => $this->expiredLot->id,
                'quantity' => -25.0,
            ]);
        });

        it('creates disposal workflow for expired products', function () {
            Queue::fake();

            $this->actingAs($this->user)
                ->postJson('/api/lots/mass-expiry-disposal', [
                    'lot_ids' => [$this->expiredLot->id],
                    'disposal_method' => 'incineration',
                    'disposal_reason' => 'Routine expiry disposal',
                    'disposal_certificate' => 'CERT-2024-001',
                ])
                ->assertOk();

            Queue::assertPushed(ProcessExpiryMovement::class);
        });
    });

    describe('FIFO/FEFO implementation', function () {
        beforeEach(function () {
            // Create test lots with strategic dates for testing rotation
            $this->fifoOldest = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'FIFO-OLD',
                'manufactured_date' => now()->subDays(30),
                'expiration_date' => now()->addDays(120),
                'quantity_remaining' => 40.0,
                'status' => 'active',
            ]);

            $this->fifoNewest = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'FIFO-NEW',
                'manufactured_date' => now()->subDays(10),
                'expiration_date' => now()->addDays(150),
                'quantity_remaining' => 60.0,
                'status' => 'active',
            ]);

            $this->fefoSoonest = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'FEFO-SOON',
                'manufactured_date' => now()->subDays(5),
                'expiration_date' => now()->addDays(15), // Expires soonest
                'quantity_remaining' => 30.0,
                'status' => 'active',
            ]);
        });

        it('implements FIFO rotation correctly', function () {
            $response = $this->actingAs($this->user)
                ->getJson("/api/products/{$this->product->id}/lots/fifo?quantity=50")
                ->assertOk();

            $lots = $response->json('data');

            // Should return oldest lot first
            expect($lots[0]['id'])->toBe($this->fifoOldest->id);
            expect($lots[0]['selection_quantity'])->toBe(40.0); // Full lot
            expect($lots[1]['id'])->toBe($this->fifoNewest->id);
            expect($lots[1]['selection_quantity'])->toBe(10.0); // Partial lot
        });

        it('implements FEFO rotation correctly', function () {
            $response = $this->actingAs($this->user)
                ->getJson("/api/products/{$this->product->id}/lots/fefo?quantity=50")
                ->assertOk();

            $lots = $response->json('data');

            // Should return soonest expiring lot first
            expect($lots[0]['id'])->toBe($this->fefoSoonest->id);
            expect($lots[0]['selection_quantity'])->toBe(30.0); // Full lot
            expect($lots[1]['id'])->toBe($this->fifoOldest->id);
            expect($lots[1]['selection_quantity'])->toBe(20.0); // Partial lot
        });

        it('recommends optimal rotation strategy based on product characteristics', function () {
            // Test product with short shelf life - should recommend FEFO
            $shortShelfProduct = Product::factory()->forCompany($this->company)->create([
                'shelf_life_days' => 30,
                'category' => 'perishable',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/products/{$shortShelfProduct->id}/rotation-strategy")
                ->assertOk();

            $strategy = $response->json('data');
            expect($strategy['recommended_strategy'])->toBe('FEFO');
            expect($strategy['reason'])->toContain('Short shelf life');

            // Test product with long shelf life - should recommend FIFO
            $longShelfProduct = Product::factory()->forCompany($this->company)->create([
                'shelf_life_days' => 365,
                'category' => 'non-perishable',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/products/{$longShelfProduct->id}/rotation-strategy")
                ->assertOk();

            $strategy = $response->json('data');
            expect($strategy['recommended_strategy'])->toBe('FIFO');
            expect($strategy['reason'])->toContain('Long shelf life');
        });

        it('handles mixed lot scenarios with hybrid strategy', function () {
            $response = $this->actingAs($this->user)
                ->getJson("/api/products/{$this->product->id}/lots/hybrid?quantity=80")
                ->assertOk();

            $lots = $response->json('data');

            // Should prioritize expiring soon lots first, then FIFO for stable lots
            expect($lots[0]['id'])->toBe($this->fefoSoonest->id);
            expect($lots[0]['selection_reason'])->toContain('FEFO');
        });
    });

    describe('lot quality and compliance tracking', function () {
        it('stores and validates quality control attributes', function () {
            $qualityData = [
                'moisture_content' => '4.8%',
                'protein_level' => '18.5%',
                'fat_content' => '2.1%',
                'microbiological_test' => 'passed',
                'heavy_metals' => 'within_limits',
                'pesticide_residue' => 'not_detected',
            ];

            $this->actingAs($this->user)
                ->postJson('/api/lots', [
                    'lot_number' => 'QC-LOT-001',
                    'product_id' => $this->product->id,
                    'supplier_id' => $this->supplier->id,
                    'manufactured_date' => now()->subDays(5)->format('Y-m-d'),
                    'expiration_date' => now()->addDays(365)->format('Y-m-d'),
                    'quantity_produced' => 100.0,
                    'unit_cost' => 25.00,
                    'quality_attributes' => $qualityData,
                    'quality_control_passed' => true,
                    'qc_inspector' => 'John Smith',
                    'qc_date' => now()->format('Y-m-d'),
                ])
                ->assertCreated();

            $this->assertDatabaseHas('product_lots', [
                'lot_number' => 'QC-LOT-001',
                'quality_control_passed' => true,
            ]);
        });

        it('tracks temperature control during storage', function () {
            $temperatureData = [
                'storage_temperature_min' => '2°C',
                'storage_temperature_max' => '8°C',
                'current_temperature' => '5°C',
                'temperature_alerts' => 0,
                'cold_chain_maintained' => true,
            ];

            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'TEMP-LOT-001',
            ]);

            $this->actingAs($this->user)
                ->patchJson("/api/lots/{$lot->slug}/temperature", [
                    'temperature_log' => $temperatureData,
                    'recorded_at' => now()->format('Y-m-d H:i:s'),
                    'recorded_by' => $this->user->id,
                ])
                ->assertOk();

            $lot->refresh();
            expect($lot->metadata['temperature_log'])->toHaveKey('storage_temperature_min');
        });

        it('implements quarantine workflow for quality issues', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'status' => 'active',
                'quantity_remaining' => 100.0,
            ]);

            $this->actingAs($this->user)
                ->patchJson("/api/lots/{$lot->slug}/quarantine", [
                    'quarantine_reason' => 'Quality control failure',
                    'quarantine_notes' => 'Moisture content exceeds specification',
                    'quarantine_duration_days' => 7,
                    'notify_quality_team' => true,
                ])
                ->assertOk();

            $lot->refresh();
            expect($lot->status)->toBe('quarantined');
            expect($lot->is_active)->toBeFalse();

            // Verify quarantined lots cannot be used in movements
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['product_lot_id' => 'No se puede mover producto de lote en cuarentena']);
        });

        it('releases quarantined lots after approval', function () {
            $quarantinedLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'status' => 'quarantined',
                'is_active' => false,
                'quarantine_reason' => 'Quality review',
            ]);

            $this->actingAs($this->user)
                ->patchJson("/api/lots/{$quarantinedLot->slug}/release", [
                    'release_reason' => 'Quality control passed',
                    'release_notes' => 'Re-testing confirmed product meets specifications',
                    'released_by_quality_manager' => true,
                ])
                ->assertOk();

            $quarantinedLot->refresh();
            expect($quarantinedLot->status)->toBe('active');
            expect($quarantinedLot->is_active)->toBeTrue();
        });
    });

    describe('lot consolidation and splitting', function () {
        it('splits large lots into smaller units', function () {
            $largeLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'LARGE-LOT-001',
                'quantity_remaining' => 1000.0,
                'status' => 'active',
            ]);

            $this->actingAs($this->user)
                ->postJson("/api/lots/{$largeLot->slug}/split", [
                    'split_quantities' => [300.0, 400.0, 300.0],
                    'new_lot_numbers' => ['SPLIT-A-001', 'SPLIT-B-001', 'SPLIT-C-001'],
                    'split_reason' => 'Distribution to multiple warehouses',
                ])
                ->assertOk();

            // Verify original lot is depleted
            $largeLot->refresh();
            expect($largeLot->quantity_remaining)->toBe(0.0);
            expect($largeLot->status)->toBe('depleted');

            // Verify new lots are created
            $this->assertDatabaseHas('product_lots', [
                'lot_number' => 'SPLIT-A-001',
                'quantity_remaining' => 300.0,
                'parent_lot_id' => $largeLot->id,
            ]);
        });

        it('consolidates small lots into larger ones', function () {
            $smallLot1 = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'SMALL-001',
                'quantity_remaining' => 50.0,
                'manufactured_date' => now()->subDays(10),
                'expiration_date' => now()->addDays(30),
            ]);

            $smallLot2 = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'SMALL-002',
                'quantity_remaining' => 75.0,
                'manufactured_date' => now()->subDays(10),
                'expiration_date' => now()->addDays(30),
            ]);

            $this->actingAs($this->user)
                ->postJson('/api/lots/consolidate', [
                    'source_lot_ids' => [$smallLot1->id, $smallLot2->id],
                    'new_lot_number' => 'CONSOLIDATED-001',
                    'consolidation_reason' => 'Optimize storage efficiency',
                ])
                ->assertOk();

            // Verify source lots are depleted
            $smallLot1->refresh();
            $smallLot2->refresh();
            expect($smallLot1->status)->toBe('consolidated');
            expect($smallLot2->status)->toBe('consolidated');

            // Verify consolidated lot is created
            $this->assertDatabaseHas('product_lots', [
                'lot_number' => 'CONSOLIDATED-001',
                'quantity_remaining' => 125.0,
            ]);
        });
    });

    describe('regulatory compliance and documentation', function () {
        it('generates lot traceability reports', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'supplier_id' => $this->supplier->id,
                'lot_number' => 'TRACE-LOT-001',
            ]);

            // Create some movements for traceability
            InventoryMovement::factory()->count(3)->create([
                'product_id' => $this->product->id,
                'product_lot_id' => $lot->id,
                'warehouse_id' => $this->warehouse->id,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/lots/{$lot->slug}/traceability-report")
                ->assertOk();

            $report = $response->json('data');
            expect($report)->toHaveKey('lot_details');
            expect($report)->toHaveKey('movement_history');
            expect($report)->toHaveKey('supplier_information');
            expect($report)->toHaveKey('quality_control_records');
            expect($report['movement_history'])->toHaveCount(3);
        });

        it('maintains audit trail for all lot changes', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'status' => 'active',
            ]);

            // Make several changes to track audit trail
            $this->actingAs($this->user)
                ->patchJson("/api/lots/{$lot->slug}", [
                    'notes' => 'Updated quality notes',
                ])
                ->assertOk();

            $this->actingAs($this->user)
                ->patchJson("/api/lots/{$lot->slug}/quarantine", [
                    'quarantine_reason' => 'Quality review',
                ])
                ->assertOk();

            $response = $this->actingAs($this->user)
                ->getJson("/api/lots/{$lot->slug}/audit-trail")
                ->assertOk();

            $auditTrail = $response->json('data');
            expect($auditTrail)->toHaveCount(3); // Created, Updated, Quarantined
            expect($auditTrail[0]['action'])->toBe('created');
            expect($auditTrail[1]['action'])->toBe('updated');
            expect($auditTrail[2]['action'])->toBe('quarantined');
        });

        it('enforces retention policies for lot records', function () {
            // Create old lot that should be archived
            $oldLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'status' => 'depleted',
                'created_at' => now()->subYears(8), // Beyond retention period
                'quantity_remaining' => 0.0,
            ]);

            Artisan::call('lots:apply-retention-policy');

            // Verify lot is archived but not deleted (for compliance)
            $oldLot->refresh();
            expect($oldLot->is_archived)->toBeTrue();
            expect($oldLot->archived_at)->not->toBeNull();
        });
    });
});
