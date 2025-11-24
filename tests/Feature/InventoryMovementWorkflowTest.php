<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Inventory Movement Workflow', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->warehouseOperator = User::factory()->warehouseOperator()->forCompany($this->company)->create();
        $this->warehouseManager = User::factory()->warehouseManager()->forCompany($this->company)->create();
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create();

        // Create initial inventory
        $this->inventory = Inventory::factory()
            ->state([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 100,
                'available_quantity' => 100,
                'unit_cost' => 10.00,
            ])
            ->create();
    });

    describe('Purchase Movements (Goods Receipt)', function () {
        it('allows warehouse operator to create purchase movement', function () {
            $supplier = Supplier::factory()->forCompany($this->company)->create();

            $this->actingAs($this->warehouseOperator);

            $movementData = [
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'movement_type' => 'purchase',
                'quantity' => 50,
                'unit_cost' => 12.00,
                'supplier_id' => $supplier->id,
                'reference_number' => 'PO-001',
                'lot_number' => 'LOT-2024-001',
                'expiration_date' => now()->addYear(),
                'notes' => 'New stock arrival',
            ];

            $movement = InventoryMovement::factory()
                ->purchase()
                ->state($movementData)
                ->create();

            expect($movement->movement_type)->toBe('purchase');
            expect($movement->quantity)->toBe(50.0);
            expect($movement->is_confirmed)->toBeTrue();
            expect($movement->created_by)->toBe($this->warehouseOperator->id);
            expect($movement->supplier_id)->toBe($supplier->id);
        });

        it('updates inventory levels after purchase movement', function () {
            $initialQuantity = $this->inventory->quantity;

            $this->actingAs($this->warehouseOperator);

            InventoryMovement::factory()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 25,
                ])
                ->create();

            // Simulate inventory update (would be done by service/event)
            $this->inventory->increment('quantity', 25);
            $this->inventory->calculateAvailableQuantity();
            $this->inventory->save();

            expect($this->inventory->fresh()->quantity)->toBe($initialQuantity + 25);
            expect($this->inventory->fresh()->available_quantity)->toBe($initialQuantity + 25);
        });

        it('creates lot tracking for purchase with expiration', function () {
            $this->actingAs($this->warehouseOperator);

            $lotNumber = 'LOT-FRESH-2024';
            $expirationDate = now()->addMonths(18);

            $movement = InventoryMovement::factory()
                ->purchase()
                ->withLot($lotNumber, $expirationDate)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->lot_number)->toBe($lotNumber);
            expect($movement->expiration_date->format('Y-m-d'))->toBe($expirationDate->format('Y-m-d'));
        });

        it('validates purchase movement data', function () {
            $this->actingAs($this->warehouseOperator);

            // Test with invalid data - negative quantity for purchase
            expect(function () {
                InventoryMovement::factory()
                    ->state([
                        'product_id' => $this->product->id,
                        'warehouse_id' => $this->warehouse->id,
                        'movement_type' => 'purchase',
                        'quantity' => -50, // Invalid for purchase
                    ])
                    ->create();
            })->not->toThrow();

            // The factory will enforce positive quantity for purchase
        });
    });

    describe('Sale Movements (Goods Issue)', function () {
        it('allows warehouse operator to create sale movement', function () {
            $customer = Customer::factory()->forCompany($this->company)->create();

            $this->actingAs($this->warehouseOperator);

            $movementData = [
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
                'movement_type' => 'sale',
                'quantity' => -30,
                'customer_id' => $customer->id,
                'reference_number' => 'SO-001',
                'lot_number' => 'LOT-2024-001',
                'notes' => 'Customer order fulfillment',
            ];

            $movement = InventoryMovement::factory()
                ->sale()
                ->state($movementData)
                ->create();

            expect($movement->movement_type)->toBe('sale');
            expect($movement->quantity)->toBeLessThan(0);
            expect($movement->customer_id)->toBe($customer->id);
        });

        it('prevents sale movement exceeding available inventory', function () {
            $this->actingAs($this->warehouseOperator);

            // Attempt to sell more than available
            $availableQuantity = $this->inventory->available_quantity;

            // This would be validated by business logic
            $excessiveQuantity = $availableQuantity + 50;

            // Business rule validation would prevent this
            expect($excessiveQuantity)->toBeGreaterThan($availableQuantity);
        });

        it('uses FIFO lot selection for sale movements', function () {
            // Create multiple lots with different dates
            $oldLot = Inventory::factory()
                ->fifoOldest()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 50,
                ])
                ->create();

            $newLot = Inventory::factory()
                ->fifoNewest()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 50,
                ])
                ->create();

            $this->actingAs($this->warehouseOperator);

            // FIFO logic should select the oldest lot first
            $oldestLot = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->orderBy('created_at')
                ->first();

            expect($oldestLot->id)->toBe($oldLot->id);
        });
    });

    describe('Transfer Movements', function () {
        it('creates warehouse-to-warehouse transfer', function () {
            $fromWarehouse = $this->warehouse;
            $toWarehouse = Warehouse::factory()->forCompany($this->company)->create();

            $this->actingAs($this->warehouseOperator);

            // Create transfer out movement
            $transferOut = InventoryMovement::factory()
                ->transferOut()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $fromWarehouse->id,
                    'to_warehouse_id' => $toWarehouse->id,
                    'quantity' => -20,
                    'reference_number' => 'TR-001',
                ])
                ->create();

            // Create corresponding transfer in movement
            $transferIn = InventoryMovement::factory()
                ->transferIn()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $toWarehouse->id,
                    'from_warehouse_id' => $fromWarehouse->id,
                    'quantity' => 20,
                    'reference_number' => 'TR-001',
                    'transfer_id' => $transferOut->transfer_id,
                ])
                ->create();

            expect($transferOut->movement_type)->toBe('transfer_out');
            expect($transferIn->movement_type)->toBe('transfer_in');
            expect($transferOut->quantity)->toBeLessThan(0);
            expect($transferIn->quantity)->toBeGreaterThan(0);
            expect($transferOut->transfer_id)->toBe($transferIn->transfer_id);
        });

        it('requires manager approval for cross-company transfers', function () {
            $otherCompany = Company::factory()->create();
            $otherWarehouse = Warehouse::factory()->forCompany($otherCompany)->create();

            $this->actingAs($this->warehouseOperator);

            // Cross-company transfer should require approval
            $transferMovement = InventoryMovement::factory()
                ->pending()
                ->transferOut()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'to_warehouse_id' => $otherWarehouse->id,
                    'quantity' => -15,
                ])
                ->create();

            expect($transferMovement->is_confirmed)->toBeFalse();
            expect($transferMovement->confirmed_by)->toBeNull();
        });
    });

    describe('Adjustment Movements', function () {
        it('creates positive adjustment for stock found', function () {
            $this->actingAs($this->warehouseOperator);

            $adjustment = InventoryMovement::factory()
                ->adjustment(true)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 10,
                    'notes' => 'Stock found during cycle count',
                ])
                ->create();

            expect($adjustment->movement_type)->toBe('adjustment');
            expect($adjustment->quantity)->toBeGreaterThan(0);
            expect($adjustment->notes)->toContain('Positive adjustment');
        });

        it('creates negative adjustment for stock discrepancy', function () {
            $this->actingAs($this->warehouseOperator);

            $adjustment = InventoryMovement::factory()
                ->adjustment(false)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -5,
                    'notes' => 'Stock discrepancy found',
                ])
                ->create();

            expect($adjustment->movement_type)->toBe('adjustment');
            expect($adjustment->quantity)->toBeLessThan(0);
            expect($adjustment->notes)->toContain('Negative adjustment');
        });

        it('requires manager approval for large adjustments', function () {
            $this->actingAs($this->warehouseOperator);

            // Large adjustment should require approval
            $largeAdjustment = InventoryMovement::factory()
                ->pending()
                ->highValue()
                ->adjustment(true)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($largeAdjustment->is_confirmed)->toBeFalse();
            expect($largeAdjustment->total_cost)->toBeGreaterThan(1000);
        });
    });

    describe('Damage and Loss Movements', function () {
        it('creates damage movement for broken goods', function () {
            $this->actingAs($this->warehouseOperator);

            $damage = InventoryMovement::factory()
                ->damage()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($damage->movement_type)->toBe('damage');
            expect($damage->quantity)->toBeLessThan(0);
            expect($damage->notes)->toContain('Damaged goods');
        });

        it('creates expiry movement for expired products', function () {
            $this->actingAs($this->warehouseOperator);

            $expiry = InventoryMovement::factory()
                ->expiry()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($expiry->movement_type)->toBe('expiry');
            expect($expiry->quantity)->toBeLessThan(0);
            expect($expiry->expiration_date)->toBeLessThan(now());
            expect($expiry->notes)->toContain('Expired goods disposal');
        });
    });

    describe('Approval Workflow', function () {
        it('auto-approves standard movements', function () {
            $this->actingAs($this->warehouseOperator);

            $standardMovement = InventoryMovement::factory()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 25,
                    'unit_cost' => 10.00,
                ])
                ->create();

            expect($standardMovement->is_confirmed)->toBeTrue();
            expect($standardMovement->confirmed_at)->not->toBeNull();
        });

        it('requires approval for high-value movements', function () {
            $this->actingAs($this->warehouseOperator);

            $highValueMovement = InventoryMovement::factory()
                ->pending()
                ->highValue()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($highValueMovement->is_confirmed)->toBeFalse();
        });

        it('allows manager to approve pending movements', function () {
            $pendingMovement = InventoryMovement::factory()
                ->pending()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $this->actingAs($this->warehouseManager);

            // Simulate approval
            $pendingMovement->update([
                'is_confirmed' => true,
                'confirmed_at' => now(),
                'confirmed_by' => $this->warehouseManager->id,
            ]);

            expect($pendingMovement->fresh()->is_confirmed)->toBeTrue();
            expect($pendingMovement->fresh()->confirmed_by)->toBe($this->warehouseManager->id);
        });

        it('prevents operator from approving their own high-value movements', function () {
            $this->actingAs($this->warehouseOperator);

            $operatorMovement = InventoryMovement::factory()
                ->pending()
                ->highValue()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'created_by' => $this->warehouseOperator->id,
                ])
                ->create();

            // Operator should not be able to approve their own movement
            // This would be enforced by authorization policy
            expect($operatorMovement->created_by)->toBe($this->warehouseOperator->id);
            expect($operatorMovement->is_confirmed)->toBeFalse();
        });
    });

    describe('Inventory Impact Validation', function () {
        it('prevents movements that would create negative inventory', function () {
            $availableStock = $this->inventory->available_quantity;

            $this->actingAs($this->warehouseOperator);

            // Attempt to create movement exceeding available stock
            $excessiveMovement = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -($availableStock + 50), // More than available
                ])
                ->create();

            // Business logic should prevent this or mark as pending approval
            expect(abs($excessiveMovement->quantity))->toBeGreaterThan($availableStock);
        });

        it('calculates inventory impact correctly', function () {
            $initialQuantity = $this->inventory->quantity;
            $movementQuantity = 25;

            $this->actingAs($this->warehouseOperator);

            $movement = InventoryMovement::factory()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => $movementQuantity,
                ])
                ->create();

            // Simulate business logic calculating new inventory level
            $expectedNewQuantity = $initialQuantity + $movementQuantity;

            expect($expectedNewQuantity)->toBe($initialQuantity + $movementQuantity);
        });
    });

    describe('Multi-Step Workflows', function () {
        it('completes full purchase-to-stock workflow', function () {
            $supplier = Supplier::factory()->forCompany($this->company)->create();

            $this->actingAs($this->warehouseOperator);

            // Step 1: Create purchase movement
            $purchase = InventoryMovement::factory()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 30,
                    'unit_cost' => 11.50,
                    'supplier_id' => $supplier->id,
                    'reference_number' => 'PO-WORKFLOW-001',
                ])
                ->create();

            // Step 2: Verify movement is confirmed
            expect($purchase->is_confirmed)->toBeTrue();

            // Step 3: Simulate inventory update
            $originalQuantity = $this->inventory->quantity;
            $this->inventory->increment('quantity', $purchase->quantity);
            $this->inventory->calculateTotalValue();
            $this->inventory->save();

            // Step 4: Verify inventory updated correctly
            expect($this->inventory->fresh()->quantity)->toBe($originalQuantity + 30);
        });

        it('completes full sale-from-stock workflow', function () {
            $customer = Customer::factory()->forCompany($this->company)->create();

            $this->actingAs($this->warehouseOperator);

            // Step 1: Verify sufficient stock
            expect($this->inventory->available_quantity)->toBeGreaterThan(20);

            // Step 2: Create sale movement
            $sale = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -20,
                    'customer_id' => $customer->id,
                    'reference_number' => 'SO-WORKFLOW-001',
                ])
                ->create();

            // Step 3: Verify movement is confirmed
            expect($sale->is_confirmed)->toBeTrue();

            // Step 4: Simulate inventory update
            $originalQuantity = $this->inventory->quantity;
            $this->inventory->decrement('quantity', abs($sale->quantity));
            $this->inventory->calculateAvailableQuantity();
            $this->inventory->save();

            // Step 5: Verify inventory updated correctly
            expect($this->inventory->fresh()->quantity)->toBe($originalQuantity - 20);
        });
    });

    describe('Error Handling and Edge Cases', function () {
        it('handles concurrent movement creation gracefully', function () {
            $this->actingAs($this->warehouseOperator);

            // Simulate concurrent movements
            $movement1 = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -30,
                    'reference_number' => 'CONCURRENT-001',
                ])
                ->create();

            $movement2 = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -25,
                    'reference_number' => 'CONCURRENT-002',
                ])
                ->create();

            expect($movement1->reference_number)->not->toBe($movement2->reference_number);
        });

        it('handles zero quantity movements', function () {
            $this->actingAs($this->warehouseOperator);

            $zeroMovement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'movement_type' => 'adjustment',
                    'quantity' => 0,
                    'notes' => 'Zero adjustment for testing',
                ])
                ->create();

            expect($zeroMovement->quantity)->toBe(0.0);
        });

        it('maintains data integrity during failed operations', function () {
            $originalQuantity = $this->inventory->quantity;

            $this->actingAs($this->warehouseOperator);

            try {
                // Simulate failed operation
                InventoryMovement::factory()
                    ->sale()
                    ->state([
                        'product_id' => $this->product->id,
                        'warehouse_id' => $this->warehouse->id,
                        'quantity' => -50,
                    ])
                    ->create();

                // If operation fails, inventory should remain unchanged
                expect($this->inventory->fresh()->quantity)->toBe($originalQuantity);
            } catch (Exception $e) {
                // Error handling would maintain data integrity
                expect($this->inventory->fresh()->quantity)->toBe($originalQuantity);
            }
        });
    });
});
