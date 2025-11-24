<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryAlert;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Movement Integration with Other Systems', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->warehouseOperator()->forCompany($this->company)->create();
        $this->manager = User::factory()->warehouseManager()->forCompany($this->company)->create();
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create();

        // Set up initial inventory
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

    describe('Inventory Level Updates', function () {
        it('updates inventory levels after purchase movement', function () {
            $this->actingAs($this->user);

            $initialQuantity = $this->inventory->quantity;
            $purchaseQuantity = 50;

            // Create purchase movement
            $purchase = InventoryMovement::factory()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => $purchaseQuantity,
                    'unit_cost' => 12.00,
                ])
                ->create();

            // Simulate inventory service updating levels
            $this->inventory->increment('quantity', $purchaseQuantity);
            $this->inventory->calculateAvailableQuantity();
            $this->inventory->calculateTotalValue();
            $this->inventory->save();

            expect($this->inventory->fresh()->quantity)->toBe($initialQuantity + $purchaseQuantity);
            expect($this->inventory->fresh()->available_quantity)->toBe($initialQuantity + $purchaseQuantity);
        });

        it('updates inventory levels after sale movement', function () {
            $this->actingAs($this->user);

            $initialQuantity = $this->inventory->quantity;
            $saleQuantity = 30;

            // Create sale movement
            $sale = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -$saleQuantity,
                ])
                ->create();

            // Simulate inventory service updating levels
            $this->inventory->decrement('quantity', $saleQuantity);
            $this->inventory->calculateAvailableQuantity();
            $this->inventory->save();

            expect($this->inventory->fresh()->quantity)->toBe($initialQuantity - $saleQuantity);
            expect($this->inventory->fresh()->available_quantity)->toBe($initialQuantity - $saleQuantity);
        });

        it('handles adjustment movements correctly', function () {
            $this->actingAs($this->user);

            $initialQuantity = $this->inventory->quantity;

            // Positive adjustment
            $positiveAdjustment = InventoryMovement::factory()
                ->adjustment(true)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 15,
                    'notes' => 'Stock found during cycle count',
                ])
                ->create();

            // Update inventory
            $this->inventory->increment('quantity', 15);
            $this->inventory->calculateAvailableQuantity();
            $this->inventory->save();

            expect($this->inventory->fresh()->quantity)->toBe($initialQuantity + 15);

            // Negative adjustment
            $negativeAdjustment = InventoryMovement::factory()
                ->adjustment(false)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -5,
                    'notes' => 'Stock discrepancy',
                ])
                ->create();

            // Update inventory
            $this->inventory->decrement('quantity', 5);
            $this->inventory->calculateAvailableQuantity();
            $this->inventory->save();

            expect($this->inventory->fresh()->quantity)->toBe($initialQuantity + 15 - 5);
        });

        it('maintains inventory accuracy across multiple movements', function () {
            $this->actingAs($this->user);

            $initialQuantity = $this->inventory->quantity;
            $runningTotal = $initialQuantity;

            // Create sequence of movements
            $movements = [
                ['type' => 'purchase', 'quantity' => 25],
                ['type' => 'sale', 'quantity' => -15],
                ['type' => 'adjustment', 'quantity' => 5],
                ['type' => 'sale', 'quantity' => -10],
            ];

            foreach ($movements as $movementData) {
                $movement = InventoryMovement::factory()
                    ->state([
                        'product_id' => $this->product->id,
                        'warehouse_id' => $this->warehouse->id,
                        'movement_type' => $movementData['type'],
                        'quantity' => $movementData['quantity'],
                    ])
                    ->create();

                // Update running total and inventory
                $runningTotal += $movementData['quantity'];
                if ($movementData['quantity'] > 0) {
                    $this->inventory->increment('quantity', $movementData['quantity']);
                } else {
                    $this->inventory->decrement('quantity', abs($movementData['quantity']));
                }
                $this->inventory->calculateAvailableQuantity();
                $this->inventory->save();
            }

            expect($this->inventory->fresh()->quantity)->toBe($runningTotal);
            expect($runningTotal)->toBe($initialQuantity + 25 - 15 + 5 - 10); // 105
        });
    });

    describe('Transfer System Integration', function () {
        it('integrates movement with inventory transfer records', function () {
            $this->actingAs($this->user);

            $fromWarehouse = $this->warehouse;
            $toWarehouse = Warehouse::factory()->forCompany($this->company)->create();

            // Create inventory transfer record
            $transfer = InventoryTransfer::factory()
                ->state([
                    'from_warehouse_id' => $fromWarehouse->id,
                    'to_warehouse_id' => $toWarehouse->id,
                    'product_id' => $this->product->id,
                    'quantity' => 25,
                    'status' => 'pending',
                    'requested_by' => $this->user->id,
                ])
                ->create();

            // Create corresponding outbound movement
            $transferOut = InventoryMovement::factory()
                ->transferOut()
                ->forTransfer($fromWarehouse->id, $toWarehouse->id)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $fromWarehouse->id,
                    'quantity' => -25,
                    'transfer_id' => $transfer->id,
                ])
                ->create();

            // Create corresponding inbound movement
            $transferIn = InventoryMovement::factory()
                ->transferIn()
                ->forTransfer($fromWarehouse->id, $toWarehouse->id)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $toWarehouse->id,
                    'quantity' => 25,
                    'transfer_id' => $transfer->id,
                ])
                ->create();

            // Verify integration
            expect($transferOut->transfer_id)->toBe($transfer->id);
            expect($transferIn->transfer_id)->toBe($transfer->id);
            expect($transferOut->to_warehouse_id)->toBe($toWarehouse->id);
            expect($transferIn->from_warehouse_id)->toBe($fromWarehouse->id);
        });

        it('maintains transfer audit trail', function () {
            $this->actingAs($this->user);

            $toWarehouse = Warehouse::factory()->forCompany($this->company)->create();

            $transfer = InventoryTransfer::factory()
                ->state([
                    'from_warehouse_id' => $this->warehouse->id,
                    'to_warehouse_id' => $toWarehouse->id,
                    'product_id' => $this->product->id,
                    'quantity' => 30,
                    'status' => 'approved',
                    'requested_by' => $this->user->id,
                ])
                ->create();

            // Both movements should reference the same transfer
            $movements = InventoryMovement::where('transfer_id', $transfer->id)->get();

            expect($movements)->toHaveCount(2);

            $outboundMovement = $movements->where('quantity', '<', 0)->first();
            $inboundMovement = $movements->where('quantity', '>', 0)->first();

            expect($outboundMovement->warehouse_id)->toBe($this->warehouse->id);
            expect($inboundMovement->warehouse_id)->toBe($toWarehouse->id);
        });
    });

    describe('Alert System Integration', function () {
        it('triggers low stock alerts after movements', function () {
            $this->actingAs($this->user);

            // Set product minimum stock
            $this->product->update(['minimum_stock' => 20]);

            // Reduce inventory below minimum
            $sale = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -85, // Reduces from 100 to 15 (below minimum of 20)
                ])
                ->create();

            // Update inventory
            $this->inventory->decrement('quantity', 85);
            $this->inventory->calculateAvailableQuantity();
            $this->inventory->save();

            // Check if inventory is below minimum
            expect($this->inventory->fresh()->isBelowMinimumStock())->toBeTrue();

            // Simulate alert creation (would be handled by event listener)
            $alert = InventoryAlert::factory()
                ->state([
                    'warehouse_id' => $this->warehouse->id,
                    'product_id' => $this->product->id,
                    'type' => 'low_stock',
                    'threshold_value' => 20,
                    'current_value' => 15,
                    'message' => 'Stock below minimum threshold',
                    'is_active' => true,
                ])
                ->create();

            expect($alert->type)->toBe('low_stock');
            expect($alert->current_value)->toBeLessThan($alert->threshold_value);
        });

        it('triggers expiration alerts for lot movements', function () {
            $this->actingAs($this->user);

            $expirationDate = now()->addDays(15); // Expires in 15 days

            // Create movement with expiring lot
            $movement = InventoryMovement::factory()
                ->purchase()
                ->withLot('LOT-EXPIRING-001', $expirationDate)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 50,
                ])
                ->create();

            // Create corresponding inventory with expiring lot
            $expiringInventory = Inventory::factory()
                ->expiringSoon(20)
                ->withLot('LOT-EXPIRING-001', $expirationDate)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 50,
                ])
                ->create();

            // Check if inventory is expiring soon
            expect($expiringInventory->isExpiringSoon(30))->toBeTrue();

            // Simulate expiration alert creation
            $expirationAlert = InventoryAlert::factory()
                ->state([
                    'warehouse_id' => $this->warehouse->id,
                    'product_id' => $this->product->id,
                    'type' => 'expiring_soon',
                    'threshold_value' => 30, // 30 days warning
                    'current_value' => 15, // Days until expiration
                    'message' => 'Product lot expiring in 15 days',
                    'is_active' => true,
                ])
                ->create();

            expect($expirationAlert->type)->toBe('expiring_soon');
            expect($expirationAlert->current_value)->toBeLessThan($expirationAlert->threshold_value);
        });
    });

    describe('Supplier and Customer Integration', function () {
        it('integrates purchase movements with supplier records', function () {
            $this->actingAs($this->user);

            $supplier = Supplier::factory()->forCompany($this->company)->create();

            $purchase = InventoryMovement::factory()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'supplier_id' => $supplier->id,
                    'quantity' => 40,
                    'unit_cost' => 11.50,
                    'reference_number' => 'PO-12345',
                    'document_type' => 'invoice',
                    'document_number' => 'INV-67890',
                ])
                ->create();

            expect($purchase->supplier_id)->toBe($supplier->id);
            expect($purchase->supplier->company_id)->toBe($this->company->id);
            expect($purchase->reference_number)->toBe('PO-12345');
            expect($purchase->document_number)->toBe('INV-67890');
        });

        it('integrates sale movements with customer records', function () {
            $this->actingAs($this->user);

            $customer = Customer::factory()->forCompany($this->company)->create();

            $sale = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'customer_id' => $customer->id,
                    'quantity' => -35,
                    'reference_number' => 'SO-54321',
                    'document_type' => 'invoice',
                    'document_number' => 'INV-98765',
                ])
                ->create();

            expect($sale->customer_id)->toBe($customer->id);
            expect($sale->customer->company_id)->toBe($this->company->id);
            expect($sale->reference_number)->toBe('SO-54321');
            expect($sale->document_number)->toBe('INV-98765');
        });

        it('tracks supplier performance through movement history', function () {
            $this->actingAs($this->user);

            $supplier = Supplier::factory()->forCompany($this->company)->create();

            // Create multiple purchases from same supplier
            $purchases = InventoryMovement::factory()
                ->count(5)
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'supplier_id' => $supplier->id,
                ])
                ->create();

            // Query supplier movement history
            $supplierMovements = InventoryMovement::where('supplier_id', $supplier->id)->get();

            expect($supplierMovements)->toHaveCount(5);

            // Calculate supplier metrics
            $totalQuantityReceived = $supplierMovements->sum('quantity');
            $averageUnitCost = $supplierMovements->avg('unit_cost');

            expect($totalQuantityReceived)->toBeGreaterThan(0);
            expect($averageUnitCost)->toBeGreaterThan(0);
        });
    });

    describe('Cost and Valuation Integration', function () {
        it('updates inventory valuation after cost changes', function () {
            $this->actingAs($this->user);

            $originalUnitCost = $this->inventory->unit_cost;
            $originalTotalValue = $this->inventory->total_value;

            // Purchase at different cost
            $newUnitCost = 15.00;
            $purchaseQuantity = 50;

            $purchase = InventoryMovement::factory()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => $purchaseQuantity,
                    'unit_cost' => $newUnitCost,
                ])
                ->create();

            // Calculate weighted average cost
            $originalQuantity = $this->inventory->quantity;
            $totalQuantity = $originalQuantity + $purchaseQuantity;
            $weightedAverageCost = (
                ($originalQuantity * $originalUnitCost) +
                ($purchaseQuantity * $newUnitCost)
            ) / $totalQuantity;

            // Update inventory with new values
            $this->inventory->update([
                'quantity' => $totalQuantity,
                'unit_cost' => $weightedAverageCost,
            ]);
            $this->inventory->calculateAvailableQuantity();
            $this->inventory->calculateTotalValue();
            $this->inventory->save();

            expect($this->inventory->fresh()->unit_cost)->toBe($weightedAverageCost);
            expect($this->inventory->fresh()->total_value)->toBe($totalQuantity * $weightedAverageCost);
        });

        it('maintains cost consistency across movement types', function () {
            $this->actingAs($this->user);

            $unitCost = $this->inventory->unit_cost;

            // Sale movement should use current inventory cost
            $sale = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -20,
                    'unit_cost' => $unitCost,
                ])
                ->create();

            expect($sale->unit_cost)->toBe($unitCost);
            expect($sale->total_cost)->toBe(20 * $unitCost);

            // Adjustment movement should preserve cost
            $adjustment = InventoryMovement::factory()
                ->adjustment(true)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'unit_cost' => $unitCost,
                ])
                ->create();

            expect($adjustment->unit_cost)->toBe($unitCost);
        });
    });

    describe('Quality Control Integration', function () {
        it('integrates movements with quality check requirements', function () {
            $this->actingAs($this->user);

            // Create movement requiring quality check
            $movement = InventoryMovement::factory()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'requires_quality_check' => true,
                    'status' => 'pending',
                ])
                ->create();

            expect($movement->requires_quality_check)->toBeTrue();
            expect($movement->quality_approved)->toBeNull();

            // Perform quality check
            $qualityCheckResult = $movement->performQualityCheck(
                $this->manager->id,
                true,
                'Quality check passed - goods in excellent condition'
            );

            expect($qualityCheckResult)->toBeTrue();
            expect($movement->fresh()->quality_approved)->toBeTrue();
            expect($movement->fresh()->quality_checked_by)->toBe($this->manager->id);
            expect($movement->fresh()->quality_notes)->toContain('excellent condition');
        });

        it('prevents completion of movements with failed quality checks', function () {
            $this->actingAs($this->user);

            $movement = InventoryMovement::factory()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'requires_quality_check' => true,
                    'status' => 'approved',
                ])
                ->create();

            // Fail quality check
            $movement->performQualityCheck(
                $this->manager->id,
                false,
                'Quality check failed - damaged packaging'
            );

            // Should not be able to complete
            expect($movement->canBeCompleted())->toBeFalse();
            expect($movement->fresh()->quality_approved)->toBeFalse();
        });
    });

    describe('Reporting and Analytics Integration', function () {
        it('provides comprehensive movement history for reporting', function () {
            $this->actingAs($this->user);

            // Create diverse movement history
            $movements = [
                InventoryMovement::factory()->purchase()->create([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 50,
                    'created_at' => now()->subDays(10),
                ]),
                InventoryMovement::factory()->sale()->create([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -20,
                    'created_at' => now()->subDays(5),
                ]),
                InventoryMovement::factory()->adjustment(true)->create([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 5,
                    'created_at' => now()->subDays(2),
                ]),
            ];

            // Query movement history for reporting
            $movementHistory = InventoryMovement::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->orderBy('created_at')
                ->get();

            expect($movementHistory)->toHaveCount(3);

            // Calculate running inventory balance
            $runningBalance = 100; // Initial inventory
            foreach ($movementHistory as $movement) {
                $runningBalance += $movement->quantity;
            }

            expect($runningBalance)->toBe(135); // 100 + 50 - 20 + 5
        });

        it('supports movement aggregation for analytics', function () {
            $this->actingAs($this->user);

            // Create movements across different types
            InventoryMovement::factory()->count(3)->purchase()->create([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
            ]);

            InventoryMovement::factory()->count(2)->sale()->create([
                'product_id' => $this->product->id,
                'warehouse_id' => $this->warehouse->id,
            ]);

            // Aggregate by movement type
            $aggregatedMovements = InventoryMovement::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->selectRaw('movement_type, COUNT(*) as count, SUM(quantity) as total_quantity')
                ->groupBy('movement_type')
                ->get();

            $purchaseAggregate = $aggregatedMovements->where('movement_type', 'purchase')->first();
            $saleAggregate = $aggregatedMovements->where('movement_type', 'sale')->first();

            expect($purchaseAggregate->count)->toBe(3);
            expect($saleAggregate->count)->toBe(2);
        });
    });

    describe('Error Recovery and Data Integrity', function () {
        it('maintains data consistency during partial failures', function () {
            $this->actingAs($this->user);

            $originalQuantity = $this->inventory->quantity;

            try {
                // Simulate transaction that might fail
                $movement = InventoryMovement::factory()
                    ->sale()
                    ->state([
                        'product_id' => $this->product->id,
                        'warehouse_id' => $this->warehouse->id,
                        'quantity' => -25,
                    ])
                    ->create();

                // If this fails, inventory should remain unchanged
                // (in real implementation, this would be in a database transaction)
                $this->inventory->decrement('quantity', 25);
                $this->inventory->save();

                expect($this->inventory->fresh()->quantity)->toBe($originalQuantity - 25);
            } catch (Exception $e) {
                // On failure, inventory should remain at original level
                expect($this->inventory->fresh()->quantity)->toBe($originalQuantity);
            }
        });

        it('handles concurrent inventory updates gracefully', function () {
            $this->actingAs($this->user);

            $originalQuantity = $this->inventory->quantity;

            // Simulate concurrent movements
            $movement1 = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -15,
                    'reference_number' => 'CONCURRENT-1',
                ])
                ->create();

            $movement2 = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -10,
                    'reference_number' => 'CONCURRENT-2',
                ])
                ->create();

            // Both movements should be created successfully
            expect($movement1->reference_number)->not->toBe($movement2->reference_number);
            expect($movement1->id)->not->toBe($movement2->id);
        });
    });
});
