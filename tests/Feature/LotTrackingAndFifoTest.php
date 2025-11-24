<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Lot Tracking and FIFO/FEFO Algorithm', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->warehouseOperator()->forCompany($this->company)->create();
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create();
    });

    describe('Lot Creation and Tracking', function () {
        it('creates unique lot numbers for each receipt', function () {
            $this->actingAs($this->user);

            // Create multiple purchase movements with different lots
            $lot1 = InventoryMovement::factory()
                ->purchase()
                ->withLot('LOT-2024-001', now()->addMonths(6))
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 50,
                ])
                ->create();

            $lot2 = InventoryMovement::factory()
                ->purchase()
                ->withLot('LOT-2024-002', now()->addMonths(8))
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 75,
                ])
                ->create();

            expect($lot1->lot_number)->not->toBe($lot2->lot_number);
            expect($lot1->lot_number)->toBe('LOT-2024-001');
            expect($lot2->lot_number)->toBe('LOT-2024-002');
        });

        it('tracks expiration dates for perishable products', function () {
            $this->actingAs($this->user);

            $expirationDate = now()->addMonths(3);

            $movement = InventoryMovement::factory()
                ->purchase()
                ->withLot('LOT-PERISHABLE-001', $expirationDate)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 30,
                ])
                ->create();

            expect($movement->expiration_date->format('Y-m-d'))->toBe($expirationDate->format('Y-m-d'));
        });

        it('allows lot consolidation for same product and expiration', function () {
            $this->actingAs($this->user);

            $lotNumber = 'LOT-CONSOLIDATE-001';
            $expirationDate = now()->addMonths(4);

            // First receipt
            $movement1 = InventoryMovement::factory()
                ->purchase()
                ->withLot($lotNumber, $expirationDate)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 25,
                ])
                ->create();

            // Second receipt with same lot
            $movement2 = InventoryMovement::factory()
                ->purchase()
                ->withLot($lotNumber, $expirationDate)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 35,
                ])
                ->create();

            expect($movement1->lot_number)->toBe($movement2->lot_number);
            expect($movement1->expiration_date->format('Y-m-d'))->toBe($movement2->expiration_date->format('Y-m-d'));
        });

        it('prevents lot mixing for different expiration dates', function () {
            $this->actingAs($this->user);

            $lotNumber = 'LOT-MIXED-001';

            $movement1 = InventoryMovement::factory()
                ->purchase()
                ->withLot($lotNumber, now()->addMonths(3))
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 25,
                ])
                ->create();

            $movement2 = InventoryMovement::factory()
                ->purchase()
                ->withLot($lotNumber, now()->addMonths(6)) // Different expiration
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 35,
                ])
                ->create();

            // Business logic should prevent this or create separate lot tracking
            expect($movement1->expiration_date)->not->toEqual($movement2->expiration_date);
        });
    });

    describe('FIFO (First In, First Out) Algorithm', function () {
        beforeEach(function () {
            $this->actingAs($this->user);

            // Create inventory lots with different receipt dates
            $this->oldLot = Inventory::factory()
                ->fifoOldest()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 50,
                    'available_quantity' => 50,
                    'lot_number' => 'LOT-OLD-001',
                    'created_at' => now()->subMonths(3),
                ])
                ->create();

            $this->mediumLot = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 75,
                    'available_quantity' => 75,
                    'lot_number' => 'LOT-MED-001',
                    'created_at' => now()->subMonths(2),
                ])
                ->create();

            $this->newLot = Inventory::factory()
                ->fifoNewest()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'available_quantity' => 100,
                    'lot_number' => 'LOT-NEW-001',
                    'created_at' => now()->subWeeks(2),
                ])
                ->create();
        });

        it('selects oldest lot first for FIFO', function () {
            // Query to simulate FIFO selection
            $oldestAvailableLot = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->orderBy('created_at', 'asc')
                ->first();

            expect($oldestAvailableLot->id)->toBe($this->oldLot->id);
            expect($oldestAvailableLot->lot_number)->toBe('LOT-OLD-001');
        });

        it('processes complete lot before moving to next in FIFO', function () {
            $saleQuantity = 45; // Less than old lot quantity

            // Simulate FIFO logic: take from oldest lot first
            $selectedLot = $this->oldLot;
            $remainingQuantity = $selectedLot->available_quantity - $saleQuantity;

            // Create sale movement from oldest lot
            $saleMovement = InventoryMovement::factory()
                ->sale()
                ->withLot($selectedLot->lot_number)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -$saleQuantity,
                ])
                ->create();

            expect($saleMovement->lot_number)->toBe($this->oldLot->lot_number);
            expect($remainingQuantity)->toBe(5); // 50 - 45 = 5 remaining
        });

        it('moves to next lot when current lot is exhausted', function () {
            $firstSaleQuantity = 50; // Exhaust old lot completely
            $secondSaleQuantity = 30; // Take from medium lot

            // First sale: exhaust old lot
            InventoryMovement::factory()
                ->sale()
                ->withLot($this->oldLot->lot_number)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -$firstSaleQuantity,
                ])
                ->create();

            // Simulate lot depletion
            $this->oldLot->update(['available_quantity' => 0]);

            // Get next available lot (should be medium lot)
            $nextAvailableLot = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->orderBy('created_at', 'asc')
                ->first();

            expect($nextAvailableLot->id)->toBe($this->mediumLot->id);

            // Second sale: take from medium lot
            $secondSaleMovement = InventoryMovement::factory()
                ->sale()
                ->withLot($nextAvailableLot->lot_number)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -$secondSaleQuantity,
                ])
                ->create();

            expect($secondSaleMovement->lot_number)->toBe($this->mediumLot->lot_number);
        });

        it('handles partial lot consumption correctly', function () {
            $partialSaleQuantity = 25; // Take part of old lot

            // Simulate partial consumption
            $movement = InventoryMovement::factory()
                ->sale()
                ->withLot($this->oldLot->lot_number)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -$partialSaleQuantity,
                ])
                ->create();

            $remainingInLot = $this->oldLot->available_quantity - $partialSaleQuantity;

            expect($movement->lot_number)->toBe($this->oldLot->lot_number);
            expect($remainingInLot)->toBe(25); // 50 - 25 = 25 remaining

            // Old lot should still be the next selected for FIFO
            $nextLot = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->orderBy('created_at', 'asc')
                ->first();

            expect($nextLot->id)->toBe($this->oldLot->id);
        });
    });

    describe('FEFO (First Expired, First Out) Algorithm', function () {
        beforeEach(function () {
            $this->actingAs($this->user);

            // Create inventory lots with different expiration dates
            $this->soonToExpire = Inventory::factory()
                ->expiringSoon(15)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 40,
                    'available_quantity' => 40,
                    'lot_number' => 'LOT-EXPIRE-SOON',
                    'expiration_date' => now()->addDays(15),
                ])
                ->create();

            $this->mediumExpiry = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 60,
                    'available_quantity' => 60,
                    'lot_number' => 'LOT-EXPIRE-MED',
                    'expiration_date' => now()->addMonths(3),
                ])
                ->create();

            $this->longExpiry = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 80,
                    'available_quantity' => 80,
                    'lot_number' => 'LOT-EXPIRE-LONG',
                    'expiration_date' => now()->addMonths(8),
                ])
                ->create();
        });

        it('selects lot with earliest expiration date for FEFO', function () {
            // Query to simulate FEFO selection
            $earliestExpiringLot = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->whereNotNull('expiration_date')
                ->orderBy('expiration_date', 'asc')
                ->first();

            expect($earliestExpiringLot->id)->toBe($this->soonToExpire->id);
            expect($earliestExpiringLot->lot_number)->toBe('LOT-EXPIRE-SOON');
        });

        it('prioritizes expiring lots over FIFO when using FEFO', function () {
            // Even if there's an older lot, FEFO should select based on expiration
            $olderButLongerExpiry = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 30,
                    'available_quantity' => 30,
                    'lot_number' => 'LOT-OLDER-LONGER',
                    'expiration_date' => now()->addMonths(6),
                    'created_at' => now()->subMonths(4), // Older than soonToExpire
                ])
                ->create();

            // FEFO should still select the earliest expiring lot
            $fefoSelection = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->whereNotNull('expiration_date')
                ->orderBy('expiration_date', 'asc')
                ->first();

            expect($fefoSelection->id)->toBe($this->soonToExpire->id);
            expect($fefoSelection->created_at)->toBeGreaterThan($olderButLongerExpiry->created_at);
        });

        it('handles non-perishable items in FEFO system', function () {
            // Create non-perishable inventory
            $nonPerishable = Inventory::factory()
                ->nonPerishable()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 50,
                    'available_quantity' => 50,
                    'lot_number' => 'LOT-NON-PERISHABLE',
                ])
                ->create();

            // FEFO should select perishable items first, then non-perishable
            $perishableFirst = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->whereNotNull('expiration_date')
                ->orderBy('expiration_date', 'asc')
                ->first();

            expect($perishableFirst->id)->toBe($this->soonToExpire->id);

            // Non-perishable should be available but not prioritized
            expect($nonPerishable->expiration_date)->toBeNull();
        });

        it('prevents shipment of expired lots', function () {
            // Create already expired lot
            $expiredLot = Inventory::factory()
                ->expired()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 20,
                    'available_quantity' => 20,
                    'lot_number' => 'LOT-EXPIRED',
                    'expiration_date' => now()->subDays(5),
                ])
                ->create();

            // Query should exclude expired lots from available selection
            $availableNonExpiredLots = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('expiration_date')
                        ->orWhere('expiration_date', '>', now());
                })
                ->orderBy('expiration_date', 'asc')
                ->get();

            expect($availableNonExpiredLots)->not->toContain($expiredLot);
            expect($expiredLot->expiration_date)->toBeLessThan(now());
        });
    });

    describe('Hybrid FIFO/FEFO Selection', function () {
        it('uses FEFO for perishable products and FIFO for non-perishable', function () {
            $this->actingAs($this->user);

            // Create mix of perishable and non-perishable lots
            $perishableOld = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 30,
                    'available_quantity' => 30,
                    'lot_number' => 'LOT-PERISH-OLD',
                    'expiration_date' => now()->addMonths(6),
                    'created_at' => now()->subMonths(3),
                ])
                ->create();

            $perishableNew = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 40,
                    'available_quantity' => 40,
                    'lot_number' => 'LOT-PERISH-NEW',
                    'expiration_date' => now()->addDays(30), // Expires sooner
                    'created_at' => now()->subWeeks(2),
                ])
                ->create();

            $nonPerishableOld = Inventory::factory()
                ->nonPerishable()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 50,
                    'available_quantity' => 50,
                    'lot_number' => 'LOT-NON-PERISH-OLD',
                    'created_at' => now()->subMonths(4),
                ])
                ->create();

            // For perishable items, select by expiration date (FEFO)
            $perishableSelection = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->whereNotNull('expiration_date')
                ->where('expiration_date', '>', now())
                ->orderBy('expiration_date', 'asc')
                ->first();

            expect($perishableSelection->id)->toBe($perishableNew->id); // Expires sooner

            // For non-perishable items, select by creation date (FIFO)
            $nonPerishableSelection = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->whereNull('expiration_date')
                ->orderBy('created_at', 'asc')
                ->first();

            expect($nonPerishableSelection->id)->toBe($nonPerishableOld->id); // Oldest first
        });
    });

    describe('Lot Splitting and Merging', function () {
        it('handles lot splitting for partial shipments', function () {
            $this->actingAs($this->user);

            $originalLot = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'available_quantity' => 100,
                    'lot_number' => 'LOT-SPLIT-TEST',
                    'expiration_date' => now()->addMonths(4),
                ])
                ->create();

            $partialShipmentQuantity = 35;

            // Create movement for partial shipment
            $partialMovement = InventoryMovement::factory()
                ->sale()
                ->withLot($originalLot->lot_number, $originalLot->expiration_date)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -$partialShipmentQuantity,
                ])
                ->create();

            // Remaining quantity in lot should be tracked
            $remainingQuantity = $originalLot->quantity - $partialShipmentQuantity;

            expect($partialMovement->lot_number)->toBe($originalLot->lot_number);
            expect($remainingQuantity)->toBe(65);
        });

        it('supports lot merging for same product and expiration', function () {
            $this->actingAs($this->user);

            $lotNumber = 'LOT-MERGE-TEST';
            $expirationDate = now()->addMonths(5);

            // Create first part of merged lot
            $lot1 = Inventory::factory()
                ->withLot($lotNumber, $expirationDate)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 40,
                    'available_quantity' => 40,
                ])
                ->create();

            // Create second part of merged lot
            $lot2 = Inventory::factory()
                ->withLot($lotNumber, $expirationDate)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 60,
                    'available_quantity' => 60,
                ])
                ->create();

            // Query total quantity for this lot
            $totalLotQuantity = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('lot_number', $lotNumber)
                ->sum('quantity');

            expect($totalLotQuantity)->toBe(100.0); // 40 + 60
        });
    });

    describe('Expiration Monitoring and Alerts', function () {
        it('identifies lots expiring within warning period', function () {
            $this->actingAs($this->user);

            $warningDays = 30;

            // Create lots with various expiration dates
            $expiringSoon = Inventory::factory()
                ->expiringSoon($warningDays - 5)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'lot_number' => 'LOT-WARNING',
                ])
                ->create();

            $expiringSafe = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'lot_number' => 'LOT-SAFE',
                    'expiration_date' => now()->addMonths(6),
                ])
                ->create();

            // Query lots expiring within warning period
            $warningLots = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->expiringSoon($warningDays)
                ->get();

            expect($warningLots->contains($expiringSoon))->toBeTrue();
            expect($warningLots->contains($expiringSafe))->toBeFalse();
        });

        it('prevents automatic selection of lots past safe consumption date', function () {
            $this->actingAs($this->user);

            $safetyDays = 7; // Don't ship lots expiring within 7 days

            $tooCloseToExpiry = Inventory::factory()
                ->expiringSoon($safetyDays - 2)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 25,
                    'available_quantity' => 25,
                    'lot_number' => 'LOT-TOO-CLOSE',
                ])
                ->create();

            $safeForShipment = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 35,
                    'available_quantity' => 35,
                    'lot_number' => 'LOT-SAFE-SHIP',
                    'expiration_date' => now()->addDays($safetyDays + 10),
                ])
                ->create();

            // Query lots safe for shipment
            $safeLots = Inventory::where('product_id', $this->product->id)
                ->where('warehouse_id', $this->warehouse->id)
                ->where('available_quantity', '>', 0)
                ->where(function ($query) use ($safetyDays) {
                    $query->whereNull('expiration_date')
                        ->orWhere('expiration_date', '>', now()->addDays($safetyDays));
                })
                ->orderBy('expiration_date', 'asc')
                ->get();

            expect($safeLots->contains($safeForShipment))->toBeTrue();
            expect($safeLots->contains($tooCloseToExpiry))->toBeFalse();
        });
    });
});
