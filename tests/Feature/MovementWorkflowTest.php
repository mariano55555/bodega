<?php

declare(strict_types=1);

use App\Events\ExpirationAlert;
use App\Events\LowStockAlert;
use App\Events\MovementCompleted;
use App\Events\MovementFailed;
use App\Events\MovementRequested;
use App\Jobs\SendMovementNotification;
use App\Jobs\UpdateInventoryLevels;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\MovementApprovalRequired;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

describe('Movement Workflow', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company)->create();
        $this->manager = User::factory()->forCompany($this->company)->create(); // Add manager role
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create();
        $this->lot = ProductLot::factory()->create([
            'product_id' => $this->product->id,
            'quantity_remaining' => 100.0,
            'status' => 'active',
        ]);
        $this->location = StorageLocation::factory()->forCompany($this->company)->forWarehouse($this->warehouse)->create();
        $this->supplier = Supplier::factory()->forCompany($this->company)->create();
        $this->customer = Customer::factory()->forCompany($this->company)->create();
        $this->reason = MovementReason::factory()->create(['type' => 'issue']);
    });

    describe('basic movement creation', function () {
        beforeEach(function () {
            Event::fake();
            Queue::fake();
        });

        it('allows warehouse operator to create outbound movement', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'storage_location_id' => $this->location->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.50,
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                    'notes' => 'Customer order #12345',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('inventory_movements', [
                'movement_type' => 'sale',
                'product_id' => $this->product->id,
                'quantity' => -10.0,
                'status' => 'pending',
            ]);

            Event::assertDispatched(MovementRequested::class);
        });

        it('allows warehouse operator to create inbound movement', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'purchase',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'storage_location_id' => $this->location->id,
                    'quantity' => 50.0,
                    'unit_cost' => 22.00,
                    'supplier_id' => $this->supplier->id,
                    'movement_reason_id' => $this->reason->id,
                    'document_number' => 'PO-001',
                    'notes' => 'Weekly restocking',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('inventory_movements', [
                'movement_type' => 'purchase',
                'product_id' => $this->product->id,
                'quantity' => 50.0,
                'status' => 'pending',
            ]);
        });

        it('creates adjustment movement for inventory corrections', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'adjustment',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'storage_location_id' => $this->location->id,
                    'quantity' => 5.0,
                    'unit_cost' => 25.00,
                    'movement_reason_id' => $this->reason->id,
                    'notes' => 'Physical count adjustment',
                ])
                ->assertCreated();

            $this->assertDatabaseHas('inventory_movements', [
                'movement_type' => 'adjustment',
                'quantity' => 5.0,
                'notes' => 'Physical count adjustment',
            ]);
        });

        it('handles transfer movements between warehouses', function () {
            $targetWarehouse = Warehouse::factory()->forCompany($this->company)->create();

            $this->actingAs($this->user)
                ->postJson('/api/movements/transfer', [
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id,
                    'from_warehouse_id' => $this->warehouse->id,
                    'to_warehouse_id' => $targetWarehouse->id,
                    'quantity' => 25.0,
                    'unit_cost' => 25.00,
                    'movement_reason_id' => $this->reason->id,
                    'notes' => 'Inter-warehouse transfer',
                ])
                ->assertCreated();

            // Should create two movements: transfer_out and transfer_in
            $this->assertDatabaseHas('inventory_movements', [
                'movement_type' => 'transfer_out',
                'warehouse_id' => $this->warehouse->id,
                'quantity' => -25.0,
            ]);

            $this->assertDatabaseHas('inventory_movements', [
                'movement_type' => 'transfer_in',
                'warehouse_id' => $targetWarehouse->id,
                'quantity' => 25.0,
            ]);
        });
    });

    describe('movement validation', function () {
        it('rejects movements with insufficient inventory', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -150.0, // More than available (100)
                    'unit_cost' => 25.50,
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['quantity' => 'Cantidad insuficiente en inventario']);
        });

        it('rejects movements from expired lots', function () {
            $expiredLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->subDays(1),
                'status' => 'expired',
                'quantity_remaining' => 50.0,
            ]);

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $expiredLot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.50,
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['product_lot_id' => 'No se puede mover producto de lote vencido']);
        });

        it('validates positive quantities for inbound movements', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'purchase',
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0, // Should be positive for purchase
                    'unit_cost' => 25.50,
                    'supplier_id' => $this->supplier->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['quantity' => 'Cantidad debe ser positiva para movimientos de entrada']);
        });

        it('validates negative quantities for outbound movements', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 10.0, // Should be negative for sale
                    'unit_cost' => 25.50,
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['quantity' => 'Cantidad debe ser negativa para movimientos de salida']);
        });

        it('prevents movements between different companies', function () {
            $otherCompany = Company::factory()->create();
            $otherWarehouse = Warehouse::factory()->forCompany($otherCompany)->create();

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'warehouse_id' => $otherWarehouse->id, // Different company
                    'quantity' => -10.0,
                    'unit_cost' => 25.50,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertForbidden();
        });
    });

    describe('approval workflow', function () {
        beforeEach(function () {
            Event::fake();
            Notification::fake();
        });

        it('requires approval for high-value movements', function () {
            $movement = $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -50.0,
                    'unit_cost' => 250.00, // High value: 12,500 total
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertCreated();

            $movementData = $movement->json('data');

            expect($movementData['status'])->toBe('pending_approval');
            expect($movementData['requires_approval'])->toBeTrue();

            Notification::assertSentTo(
                $this->manager,
                MovementApprovalRequired::class
            );
        });

        it('auto-approves low-value movements', function () {
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -5.0,
                    'unit_cost' => 10.00, // Low value: 50 total
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertCreated();

            $this->assertDatabaseHas('inventory_movements', [
                'status' => 'approved',
                'requires_approval' => false,
            ]);
        });

        it('allows manager to approve pending movements', function () {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'pending_approval',
                'requires_approval' => true,
                'total_cost' => 15000.00,
            ]);

            $this->actingAs($this->manager)
                ->patchJson("/api/movements/{$movement->id}/approve", [
                    'approval_notes' => 'Approved for large customer order',
                ])
                ->assertOk();

            $movement->refresh();
            expect($movement->status)->toBe('approved');
            expect($movement->approved_by)->toBe($this->manager->id);
            expect($movement->approved_at)->not->toBeNull();
        });

        it('allows manager to reject pending movements', function () {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'pending_approval',
                'requires_approval' => true,
            ]);

            $this->actingAs($this->manager)
                ->patchJson("/api/movements/{$movement->id}/reject", [
                    'rejection_reason' => 'Insufficient justification',
                ])
                ->assertOk();

            $movement->refresh();
            expect($movement->status)->toBe('rejected');
            expect($movement->rejected_by)->toBe($this->manager->id);
            expect($movement->rejected_at)->not->toBeNull();
        });

        it('prevents non-managers from approving movements', function () {
            $movement = InventoryMovement::factory()->create([
                'status' => 'pending_approval',
                'requires_approval' => true,
            ]);

            $this->actingAs($this->user)
                ->patchJson("/api/movements/{$movement->id}/approve")
                ->assertForbidden();
        });
    });

    describe('movement execution', function () {
        beforeEach(function () {
            Event::fake();
            Queue::fake();
        });

        it('executes approved movement and updates inventory', function () {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'product_lot_id' => $this->lot->id,
                'warehouse_id' => $this->warehouse->id,
                'movement_type' => 'sale',
                'quantity' => -15.0,
                'unit_cost' => 25.00,
                'status' => 'approved',
            ]);

            $initialQuantity = $this->lot->quantity_remaining;

            $this->actingAs($this->user)
                ->postJson("/api/movements/{$movement->id}/execute")
                ->assertOk();

            $movement->refresh();
            $this->lot->refresh();

            expect($movement->status)->toBe('completed');
            expect($movement->executed_at)->not->toBeNull();
            expect($movement->executed_by)->toBe($this->user->id);
            expect($this->lot->quantity_remaining)->toBe($initialQuantity + $movement->quantity);

            Event::assertDispatched(MovementCompleted::class);
            Queue::assertPushed(UpdateInventoryLevels::class);
        });

        it('handles movement execution failures gracefully', function () {
            // Create movement that will fail (insufficient inventory)
            $this->lot->update(['quantity_remaining' => 5.0]);

            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'product_lot_id' => $this->lot->id,
                'warehouse_id' => $this->warehouse->id,
                'movement_type' => 'sale',
                'quantity' => -15.0, // More than available
                'status' => 'approved',
            ]);

            $this->actingAs($this->user)
                ->postJson("/api/movements/{$movement->id}/execute")
                ->assertUnprocessable();

            $movement->refresh();
            expect($movement->status)->toBe('failed');
            expect($movement->failure_reason)->toContain('Insufficient inventory');

            Event::assertDispatched(MovementFailed::class);
        });

        it('prevents execution of unapproved movements', function () {
            $movement = InventoryMovement::factory()->create([
                'status' => 'pending_approval',
            ]);

            $this->actingAs($this->user)
                ->postJson("/api/movements/{$movement->id}/execute")
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['status' => 'Movimiento debe estar aprobado para ejecutar']);
        });

        it('prevents double execution of completed movements', function () {
            $movement = InventoryMovement::factory()->create([
                'status' => 'completed',
            ]);

            $this->actingAs($this->user)
                ->postJson("/api/movements/{$movement->id}/execute")
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['status' => 'Movimiento ya ha sido ejecutado']);
        });
    });

    describe('FIFO/FEFO lot selection', function () {
        beforeEach(function () {
            // Create multiple lots with different dates
            $this->oldLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(30),
                'expiration_date' => now()->addDays(60),
                'quantity_remaining' => 40.0,
                'status' => 'active',
            ]);

            $this->newLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(10),
                'expiration_date' => now()->addDays(20),
                'quantity_remaining' => 60.0,
                'status' => 'active',
            ]);
        });

        it('automatically selects lots using FIFO strategy', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/movements/with-lot-selection', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -30.0,
                    'unit_cost' => 25.00,
                    'rotation_strategy' => 'FIFO',
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertCreated();

            $movements = $response->json('data');
            expect($movements[0]['product_lot_id'])->toBe($this->oldLot->id); // Oldest lot selected first
        });

        it('automatically selects lots using FEFO strategy', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/movements/with-lot-selection', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -30.0,
                    'unit_cost' => 25.00,
                    'rotation_strategy' => 'FEFO',
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertCreated();

            $movements = $response->json('data');
            expect($movements[0]['product_lot_id'])->toBe($this->newLot->id); // Expires sooner, selected first
        });

        it('creates multiple movements when spanning multiple lots', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/movements/with-lot-selection', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -80.0, // Requires both lots
                    'unit_cost' => 25.00,
                    'rotation_strategy' => 'FIFO',
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertCreated();

            $movements = $response->json('data');
            expect($movements)->toHaveCount(2); // Two movements created
            expect($movements[0]['quantity'])->toBe(-40.0); // Full old lot
            expect($movements[1]['quantity'])->toBe(-40.0); // Partial new lot
        });
    });

    describe('notifications and alerts', function () {
        beforeEach(function () {
            Event::fake();
            Notification::fake();
        });

        it('triggers low stock alerts when inventory falls below threshold', function () {
            $this->product->update(['min_stock_level' => 20.0]);

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -85.0, // Will leave 15, below minimum of 20
                    'unit_cost' => 25.00,
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertCreated();

            Event::assertDispatched(LowStockAlert::class);
        });

        it('triggers expiration alerts for soon-to-expire lots', function () {
            $expiringSoonLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->addDays(7), // Expires in a week
                'quantity_remaining' => 30.0,
                'status' => 'active',
            ]);

            // Movement that leaves expiring lot untouched should trigger alert
            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id, // Use non-expiring lot
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertCreated();

            Event::assertDispatched(ExpirationAlert::class);
        });

        it('sends completion notifications to relevant parties', function () {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'approved',
                'customer_id' => $this->customer->id,
            ]);

            $this->actingAs($this->user)
                ->postJson("/api/movements/{$movement->id}/execute")
                ->assertOk();

            Queue::assertPushed(SendMovementNotification::class);
        });
    });

    describe('audit trail and tracking', function () {
        it('maintains complete audit trail for movement lifecycle', function () {
            $movement = $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertCreated();

            $movementData = $movement->json('data');

            // Check creation audit data
            expect($movementData['created_by'])->toBe($this->user->id);
            expect($movementData['reference_number'])->not->toBeNull();
            expect($movementData['metadata'])->toHaveKey('created_ip');
            expect($movementData['metadata'])->toHaveKey('user_agent');
        });

        it('tracks all status changes with timestamps', function () {
            $movement = InventoryMovement::factory()->create([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'status' => 'pending_approval',
            ]);

            // Approve movement
            $this->actingAs($this->manager)
                ->patchJson("/api/movements/{$movement->id}/approve")
                ->assertOk();

            // Execute movement
            $this->actingAs($this->user)
                ->postJson("/api/movements/{$movement->id}/execute")
                ->assertOk();

            $movement->refresh();

            expect($movement->approved_at)->not->toBeNull();
            expect($movement->approved_by)->toBe($this->manager->id);
            expect($movement->executed_at)->not->toBeNull();
            expect($movement->executed_by)->toBe($this->user->id);
        });
    });

    describe('error scenarios and recovery', function () {
        it('handles concurrent movement requests safely', function () {
            // Simulate concurrent requests that could cause race conditions
            $requests = collect(range(1, 5))->map(function () {
                return [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -25.0, // Each wants 25 units
                    'unit_cost' => 25.00,
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ];
            });

            $responses = $requests->map(function ($request) {
                return $this->actingAs($this->user)->postJson('/api/movements', $request);
            });

            // Some should succeed, others should fail due to insufficient inventory
            $successCount = $responses->filter(fn ($r) => $r->status() === 201)->count();
            $failureCount = $responses->filter(fn ($r) => $r->status() === 422)->count();

            expect($successCount + $failureCount)->toBe(5);
            expect($successCount)->toBeLessThanOrEqual(4); // Max 100 units available
        });

        it('rolls back failed movements completely', function () {
            // Mock a scenario where movement creation partially succeeds then fails
            $initialLotQuantity = $this->lot->quantity_remaining;

            // Attempt movement that will fail during execution
            $this->mock(\App\Services\MovementService::class)
                ->shouldReceive('execute')
                ->andThrow(new \Exception('Payment processing failed'));

            $this->actingAs($this->user)
                ->postJson('/api/movements', [
                    'movement_type' => 'sale',
                    'product_id' => $this->product->id,
                    'product_lot_id' => $this->lot->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10.0,
                    'unit_cost' => 25.00,
                    'customer_id' => $this->customer->id,
                    'movement_reason_id' => $this->reason->id,
                ])
                ->assertStatus(500);

            // Verify inventory levels unchanged
            $this->lot->refresh();
            expect($this->lot->quantity_remaining)->toBe($initialLotQuantity);
        });
    });
});
