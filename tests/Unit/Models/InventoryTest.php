<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Inventory Model', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company)->create();
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create();
    });

    describe('relationships', function () {
        it('belongs to product', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($inventory->product)->toBeInstanceOf(Product::class);
            expect($inventory->product->id)->toBe($this->product->id);
        });

        it('belongs to warehouse', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($inventory->warehouse)->toBeInstanceOf(Warehouse::class);
            expect($inventory->warehouse->id)->toBe($this->warehouse->id);
        });

        it('belongs to creator user', function () {
            $this->actingAs($this->user);
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($inventory->creator)->toBeInstanceOf(User::class);
            expect($inventory->creator->id)->toBe($this->user->id);
        });

        it('belongs to updater user when updated', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $this->actingAs($this->user);
            $inventory->update(['quantity' => 200]);

            expect($inventory->fresh()->updater)->toBeInstanceOf(User::class);
            expect($inventory->fresh()->updater->id)->toBe($this->user->id);
        });

        it('belongs to deleter user when soft deleted', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $this->actingAs($this->user);
            $inventory->delete();

            expect($inventory->fresh()->deleter)->toBeInstanceOf(User::class);
            expect($inventory->fresh()->deleter->id)->toBe($this->user->id);
        });

        it('belongs to last counter user', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'last_counted_by' => $this->user->id,
                ])
                ->create();

            expect($inventory->lastCounter)->toBeInstanceOf(User::class);
            expect($inventory->lastCounter->id)->toBe($this->user->id);
        });
    });

    describe('quantity calculations', function () {
        it('calculates available quantity automatically on creation', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'reserved_quantity' => 25,
                ])
                ->create();

            expect($inventory->available_quantity)->toBe(75.0);
        });

        it('recalculates available quantity when quantity changes', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'reserved_quantity' => 25,
                ])
                ->create();

            $inventory->update(['quantity' => 150]);

            expect($inventory->fresh()->available_quantity)->toBe(125.0);
        });

        it('recalculates available quantity when reserved quantity changes', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'reserved_quantity' => 25,
                ])
                ->create();

            $inventory->update(['reserved_quantity' => 40]);

            expect($inventory->fresh()->available_quantity)->toBe(60.0);
        });

        it('calculates total value automatically on creation', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'unit_cost' => 15.50,
                ])
                ->create();

            expect($inventory->total_value)->toBe(1550.0);
        });

        it('recalculates total value when quantity changes', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'unit_cost' => 15.50,
                ])
                ->create();

            $inventory->update(['quantity' => 200]);

            expect($inventory->fresh()->total_value)->toBe(3100.0);
        });

        it('recalculates total value when unit cost changes', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'unit_cost' => 15.50,
                ])
                ->create();

            $inventory->update(['unit_cost' => 20.00]);

            expect($inventory->fresh()->total_value)->toBe(2000.0);
        });

        it('handles zero cost calculation', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'unit_cost' => null,
                ])
                ->create();

            expect($inventory->total_value)->toBe(0.0);
        });
    });

    describe('active status management', function () {
        it('sets active_at when is_active is true on creation', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'is_active' => true,
                ])
                ->create();

            expect($inventory->active_at)->not->toBeNull();
        });

        it('does not set active_at when is_active is false on creation', function () {
            $inventory = Inventory::factory()
                ->inactive()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($inventory->active_at)->toBeNull();
        });

        it('updates active_at when is_active changes to true', function () {
            $inventory = Inventory::factory()
                ->inactive()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $inventory->update(['is_active' => true]);

            expect($inventory->fresh()->active_at)->not->toBeNull();
        });

        it('clears active_at when is_active changes to false', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'is_active' => true,
                ])
                ->create();

            $inventory->update(['is_active' => false]);

            expect($inventory->fresh()->active_at)->toBeNull();
        });
    });

    describe('scopes', function () {
        it('filters active inventory', function () {
            $activeInventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'is_active' => true,
                ])
                ->create();

            $inactiveInventory = Inventory::factory()
                ->inactive()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $activeResults = Inventory::active()->get();

            expect($activeResults->contains($activeInventory))->toBeTrue();
            expect($activeResults->contains($inactiveInventory))->toBeFalse();
        });

        it('filters available inventory', function () {
            $availableInventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'reserved_quantity' => 25,
                ])
                ->create();

            $outOfStockInventory = Inventory::factory()
                ->outOfStock()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $availableResults = Inventory::available()->get();

            expect($availableResults->contains($availableInventory))->toBeTrue();
            expect($availableResults->contains($outOfStockInventory))->toBeFalse();
        });

        it('filters inventory expiring soon', function () {
            $expiringSoonInventory = Inventory::factory()
                ->expiringSoon(15)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $longExpiryInventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'expiration_date' => now()->addMonths(6),
                ])
                ->create();

            $nonPerishableInventory = Inventory::factory()
                ->nonPerishable()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $expiringSoonResults = Inventory::expiringSoon(30)->get();

            expect($expiringSoonResults->contains($expiringSoonInventory))->toBeTrue();
            expect($expiringSoonResults->contains($longExpiryInventory))->toBeFalse();
            expect($expiringSoonResults->contains($nonPerishableInventory))->toBeFalse();
        });
    });

    describe('business logic methods', function () {
        it('detects inventory below minimum stock', function () {
            $productWithMinStock = Product::factory()
                ->forCompany($this->company)
                ->state(['minimum_stock' => 50])
                ->create();

            $lowStockInventory = Inventory::factory()
                ->belowMinimum()
                ->state([
                    'product_id' => $productWithMinStock->id,
                    'warehouse_id' => $this->warehouse->id,
                    'available_quantity' => 25,
                ])
                ->create();

            expect($lowStockInventory->isBelowMinimumStock())->toBeTrue();
        });

        it('detects inventory above minimum stock', function () {
            $productWithMinStock = Product::factory()
                ->forCompany($this->company)
                ->state(['minimum_stock' => 50])
                ->create();

            $sufficientStockInventory = Inventory::factory()
                ->state([
                    'product_id' => $productWithMinStock->id,
                    'warehouse_id' => $this->warehouse->id,
                    'available_quantity' => 100,
                ])
                ->create();

            expect($sufficientStockInventory->isBelowMinimumStock())->toBeFalse();
        });

        it('detects expired inventory', function () {
            $expiredInventory = Inventory::factory()
                ->expired()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($expiredInventory->isExpired())->toBeTrue();
        });

        it('detects non-expired inventory', function () {
            $freshInventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'expiration_date' => now()->addMonths(6),
                ])
                ->create();

            expect($freshInventory->isExpired())->toBeFalse();
        });

        it('handles non-perishable items for expiration check', function () {
            $nonPerishableInventory = Inventory::factory()
                ->nonPerishable()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($nonPerishableInventory->isExpired())->toBeFalse();
        });

        it('detects inventory expiring soon', function () {
            $expiringSoonInventory = Inventory::factory()
                ->expiringSoon(15)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($expiringSoonInventory->isExpiringSoon(30))->toBeTrue();
        });

        it('detects inventory not expiring soon', function () {
            $longExpiryInventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'expiration_date' => now()->addMonths(6),
                ])
                ->create();

            expect($longExpiryInventory->isExpiringSoon(30))->toBeFalse();
        });
    });

    describe('lot tracking', function () {
        it('creates inventory with lot tracking', function () {
            $lotNumber = 'LOT-TEST-2024';
            $expirationDate = now()->addMonths(6);

            $inventory = Inventory::factory()
                ->withLot($lotNumber, $expirationDate)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($inventory->lot_number)->toBe($lotNumber);
            expect($inventory->expiration_date->format('Y-m-d'))->toBe($expirationDate->format('Y-m-d'));
        });

        it('creates inventory without lot tracking', function () {
            $inventory = Inventory::factory()
                ->nonPerishable()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'lot_number' => null,
                ])
                ->create();

            expect($inventory->lot_number)->toBeNull();
            expect($inventory->expiration_date)->toBeNull();
        });
    });

    describe('FIFO/FEFO testing scenarios', function () {
        it('creates older lot for FIFO testing', function () {
            $oldLot = Inventory::factory()
                ->fifoOldest()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($oldLot->lot_number)->toContain('LOT-OLD-');
            expect($oldLot->created_at)->toBeLessThan(now()->subMonths(2));
        });

        it('creates newer lot for FIFO testing', function () {
            $newLot = Inventory::factory()
                ->fifoNewest()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($newLot->lot_number)->toContain('LOT-NEW-');
            expect($newLot->created_at)->toBeGreaterThan(now()->subMonth());
        });

        it('supports FEFO (First Expired First Out) scenario', function () {
            $soonToExpire = Inventory::factory()
                ->expiringSoon(15)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $laterToExpire = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'expiration_date' => now()->addMonths(6),
                ])
                ->create();

            expect($soonToExpire->expiration_date)->toBeLessThan($laterToExpire->expiration_date);
        });
    });

    describe('stock level scenarios', function () {
        it('creates out of stock inventory', function () {
            $outOfStock = Inventory::factory()
                ->outOfStock()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($outOfStock->quantity)->toBe(0.0);
            expect($outOfStock->available_quantity)->toBe(0.0);
            expect($outOfStock->total_value)->toBe(0.0);
        });

        it('creates highly reserved inventory', function () {
            $highlyReserved = Inventory::factory()
                ->highlyReserved()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($highlyReserved->reserved_quantity)->toBeGreaterThan($highlyReserved->quantity * 0.7);
            expect($highlyReserved->available_quantity)->toBeLessThan($highlyReserved->quantity * 0.3);
        });

        it('creates inventory below minimum stock', function () {
            $lowStock = Inventory::factory()
                ->belowMinimum()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($lowStock->quantity)->toBeLessThan(10);
            expect($lowStock->available_quantity)->toBeLessThan(10);
        });
    });

    describe('cycle count tracking', function () {
        it('tracks last count information', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                    'last_count_quantity' => 100,
                    'last_counted_at' => now()->subDays(5),
                    'last_counted_by' => $this->user->id,
                ])
                ->create();

            expect($inventory->last_count_quantity)->toBe(100.0);
            expect($inventory->last_counted_at)->not->toBeNull();
            expect($inventory->last_counted_by)->toBe($this->user->id);
        });

        it('creates inventory with count discrepancy', function () {
            $discrepancyInventory = Inventory::factory()
                ->withCountDiscrepancy()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($discrepancyInventory->quantity)->not->toBe($discrepancyInventory->last_count_quantity);
        });
    });

    describe('storage location management', function () {
        it('creates inventory at specific location', function () {
            $location = 'A01-B02-C03';
            $inventory = Inventory::factory()
                ->atLocation($location)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($inventory->location)->toBe($location);
        });
    });

    describe('audit trail', function () {
        it('records creator when user is authenticated', function () {
            $this->actingAs($this->user);

            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($inventory->created_by)->toBe($this->user->id);
        });

        it('records updater when inventory is modified', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $this->actingAs($this->user);
            $inventory->update(['quantity' => 200]);

            expect($inventory->fresh()->updated_by)->toBe($this->user->id);
        });

        it('supports soft deletion with deleter tracking', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $inventoryId = $inventory->id;

            $this->actingAs($this->user);
            $inventory->delete();

            expect(Inventory::find($inventoryId))->toBeNull();
            expect(Inventory::withTrashed()->find($inventoryId))->not->toBeNull();
            expect(Inventory::withTrashed()->find($inventoryId)->deleted_by)->toBe($this->user->id);
        });
    });

    describe('casts and data types', function () {
        it('casts quantities to decimal', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 123.4567,
                    'reserved_quantity' => 23.1234,
                    'available_quantity' => 100.3333,
                    'unit_cost' => 45.6789,
                    'total_value' => 5678.9012,
                    'last_count_quantity' => 122.0000,
                ])
                ->create();

            expect($inventory->quantity)->toBeFloat();
            expect($inventory->reserved_quantity)->toBeFloat();
            expect($inventory->available_quantity)->toBeFloat();
            expect($inventory->unit_cost)->toBeFloat();
            expect($inventory->total_value)->toBeFloat();
            expect($inventory->last_count_quantity)->toBeFloat();
        });

        it('casts dates properly', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'expiration_date' => '2025-12-31',
                    'last_counted_at' => '2024-01-15 10:30:00',
                ])
                ->create();

            expect($inventory->expiration_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($inventory->last_counted_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($inventory->active_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        it('casts is_active to boolean', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'is_active' => 1,
                ])
                ->create();

            expect($inventory->is_active)->toBeBool();
            expect($inventory->is_active)->toBeTrue();
        });
    });

    describe('edge cases', function () {
        it('handles fractional quantities correctly', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 0.1234,
                    'reserved_quantity' => 0.0234,
                ])
                ->create();

            expect($inventory->quantity)->toBe(0.1234);
            expect($inventory->available_quantity)->toBe(0.1000);
        });

        it('handles very small quantities', function () {
            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 0.0001,
                    'unit_cost' => 10000.0000,
                ])
                ->create();

            expect($inventory->quantity)->toBe(0.0001);
            expect($inventory->total_value)->toBe(1.0000);
        });

        it('handles null expiration dates gracefully', function () {
            $inventory = Inventory::factory()
                ->nonPerishable()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($inventory->expiration_date)->toBeNull();
            expect($inventory->isExpired())->toBeFalse();
            expect($inventory->isExpiringSoon())->toBeFalse();
        });

        it('maintains data integrity when user is not authenticated', function () {
            auth()->logout();

            $inventory = Inventory::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($inventory->created_by)->toBeNull();
            expect($inventory->available_quantity)->not->toBeNull();
            expect($inventory->total_value)->not->toBeNull();
        });
    });
});
