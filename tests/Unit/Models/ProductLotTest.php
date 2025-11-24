<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ProductLot Model', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create();
        $this->supplier = Supplier::factory()->forCompany($this->company)->create();
    });

    describe('relationships', function () {
        it('belongs to product', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
            ]);

            expect($lot->product)->toBeInstanceOf(Product::class);
            expect($lot->product->id)->toBe($this->product->id);
        });

        it('belongs to supplier', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'supplier_id' => $this->supplier->id,
            ]);

            expect($lot->supplier)->toBeInstanceOf(Supplier::class);
            expect($lot->supplier->id)->toBe($this->supplier->id);
        });

        it('belongs to creator user', function () {
            $this->actingAs($this->user);

            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
            ]);

            expect($lot->creator)->toBeInstanceOf(User::class);
            expect($lot->creator->id)->toBe($this->user->id);
        });

        it('has many movements', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
            ]);

            expect($lot->movements())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        });
    });

    describe('quantity calculations', function () {
        it('calculates remaining quantity correctly without movements', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_produced' => 100.0,
                'quantity_remaining' => 75.0,
            ]);

            expect($lot->calculateRemainingQuantity())->toBe(100.0);
        });

        it('calculates remaining quantity with movements', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_produced' => 100.0,
                'quantity_remaining' => 75.0,
            ]);

            // Create some movements
            InventoryMovement::factory()->create([
                'product_lot_id' => $lot->id,
                'movement_type' => 'out',
                'quantity' => -10.0,
                'status' => 'completed',
            ]);

            InventoryMovement::factory()->create([
                'product_lot_id' => $lot->id,
                'movement_type' => 'adjustment',
                'quantity' => 5.0,
                'status' => 'completed',
            ]);

            expect($lot->calculateRemainingQuantity())->toBe(95.0); // 100 + 5 - 10
        });

        it('returns quantity used attribute correctly', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_produced' => 100.0,
                'quantity_remaining' => 75.0,
            ]);

            expect($lot->quantity_used)->toBe(25.0);
        });

        it('calculates quantity remaining percentage correctly', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_produced' => 100.0,
                'quantity_remaining' => 75.0,
            ]);

            expect($lot->quantity_remaining_percentage)->toBe(75.0);
        });

        it('handles zero quantity produced edge case', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_produced' => 0.0,
                'quantity_remaining' => 0.0,
            ]);

            expect($lot->quantity_remaining_percentage)->toBe(0.0);
        });
    });

    describe('expiration management', function () {
        it('identifies expired lots correctly', function () {
            $expiredLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->subDays(1),
            ]);

            $activeLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->addDays(30),
            ]);

            expect($expiredLot->isExpired())->toBeTrue();
            expect($activeLot->isExpired())->toBeFalse();
        });

        it('identifies lots expiring soon correctly', function () {
            $expiringSoonLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->addDays(15),
            ]);

            $futureLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->addDays(60),
            ]);

            expect($expiringSoonLot->isExpiringSoon(30))->toBeTrue();
            expect($futureLot->isExpiringSoon(30))->toBeFalse();
        });

        it('calculates days until expiration correctly', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->addDays(15),
            ]);

            expect($lot->daysUntilExpiration())->toBe(15);
        });

        it('handles lots without expiration date', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => null,
            ]);

            expect($lot->daysUntilExpiration())->toBeNull();
            expect($lot->isExpired())->toBeFalse();
            expect($lot->isExpiringSoon())->toBeFalse();
        });

        it('updates expiration status automatically', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => now()->subDays(1),
                'status' => 'active',
            ]);

            $lot->updateExpirationStatus();

            expect($lot->fresh()->status)->toBe('expired');
        });
    });

    describe('availability checks', function () {
        it('determines availability correctly for active lot', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'is_active' => true,
                'status' => 'active',
                'quantity_remaining' => 50.0,
                'expiration_date' => now()->addDays(30),
            ]);

            expect($lot->isAvailable())->toBeTrue();
        });

        it('determines unavailability for expired lot', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'is_active' => true,
                'status' => 'active',
                'quantity_remaining' => 50.0,
                'expiration_date' => now()->subDays(1),
            ]);

            expect($lot->isAvailable())->toBeFalse();
        });

        it('determines unavailability for zero quantity lot', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'is_active' => true,
                'status' => 'active',
                'quantity_remaining' => 0.0,
                'expiration_date' => now()->addDays(30),
            ]);

            expect($lot->isAvailable())->toBeFalse();
        });

        it('determines unavailability for inactive lot', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'is_active' => false,
                'status' => 'active',
                'quantity_remaining' => 50.0,
                'expiration_date' => now()->addDays(30),
            ]);

            expect($lot->isAvailable())->toBeFalse();
        });
    });

    describe('quantity manipulation', function () {
        it('reduces quantity correctly when sufficient', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_remaining' => 50.0,
            ]);

            $result = $lot->reduceQuantity(20.0);

            expect($result)->toBeTrue();
            expect($lot->fresh()->quantity_remaining)->toBe(30.0);
        });

        it('fails to reduce quantity when insufficient', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_remaining' => 10.0,
            ]);

            $result = $lot->reduceQuantity(20.0);

            expect($result)->toBeFalse();
            expect($lot->fresh()->quantity_remaining)->toBe(10.0);
        });

        it('increases quantity correctly', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_remaining' => 50.0,
            ]);

            $result = $lot->increaseQuantity(25.0);

            expect($result)->toBeTrue();
            expect($lot->fresh()->quantity_remaining)->toBe(75.0);
        });
    });

    describe('scopes', function () {
        beforeEach(function () {
            $this->activeLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'is_active' => true,
                'status' => 'active',
                'active_at' => now(),
                'quantity_remaining' => 50.0,
            ]);

            $this->expiredLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'status' => 'expired',
                'expiration_date' => now()->subDays(1),
            ]);

            $this->expiringSoonLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'status' => 'active',
                'expiration_date' => now()->addDays(15),
            ]);
        });

        it('filters active lots correctly', function () {
            $activeLots = ProductLot::active()->get();

            expect($activeLots)->toHaveCount(1);
            expect($activeLots->first()->id)->toBe($this->activeLot->id);
        });

        it('filters expired lots correctly', function () {
            $expiredLots = ProductLot::expired()->get();

            expect($expiredLots)->toContain(
                fn ($lot) => $lot->id === $this->expiredLot->id
            );
        });

        it('filters lots expiring soon correctly', function () {
            $expiringSoonLots = ProductLot::expiringSoon(30)->get();

            expect($expiringSoonLots)->toContain(
                fn ($lot) => $lot->id === $this->expiringSoonLot->id
            );
        });

        it('filters available lots correctly', function () {
            $availableLots = ProductLot::available()->get();

            expect($availableLots)->toHaveCount(2); // active and expiring soon lots
        });

        it('filters by product correctly', function () {
            $otherProduct = Product::factory()->forCompany($this->company)->create();
            ProductLot::factory()->create(['product_id' => $otherProduct->id]);

            $productLots = ProductLot::forProduct($this->product->id)->get();

            expect($productLots)->toHaveCount(3); // All our test lots
        });
    });

    describe('FIFO and FEFO ordering', function () {
        beforeEach(function () {
            $this->oldLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(30),
                'expiration_date' => now()->addDays(60),
                'created_at' => now()->subHours(2),
            ]);

            $this->newLot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => now()->subDays(10),
                'expiration_date' => now()->addDays(30),
                'created_at' => now()->subHours(1),
            ]);
        });

        it('orders by FIFO correctly', function () {
            $fifoLots = ProductLot::fifo()->get();

            expect($fifoLots->first()->id)->toBe($this->oldLot->id);
            expect($fifoLots->last()->id)->toBe($this->newLot->id);
        });

        it('orders by FEFO correctly', function () {
            $fefoLots = ProductLot::fefo()->get();

            expect($fefoLots->first()->id)->toBe($this->newLot->id); // Expires sooner
            expect($fefoLots->last()->id)->toBe($this->oldLot->id);
        });
    });

    describe('model events and attributes', function () {
        it('auto-generates slug from lot number on creation', function () {
            $lot = ProductLot::factory()->make([
                'product_id' => $this->product->id,
                'lot_number' => 'LOT TEST 001',
                'slug' => null,
            ]);

            $lot->save();

            expect($lot->slug)->toBe('lot-test-001');
        });

        it('sets created_by when user is authenticated', function () {
            $this->actingAs($this->user);

            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
            ]);

            expect($lot->created_by)->toBe($this->user->id);
        });

        it('sets active_at when is_active is true', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'is_active' => true,
                'active_at' => null,
            ]);

            expect($lot->active_at)->not->toBeNull();
        });

        it('initializes quantity_remaining to quantity_produced', function () {
            $lot = ProductLot::factory()->make([
                'product_id' => $this->product->id,
                'quantity_produced' => 100.0,
                'quantity_remaining' => null,
            ]);

            $lot->save();

            expect($lot->quantity_remaining)->toBe(100.0);
        });

        it('tracks deleted_by on soft deletion', function () {
            $this->actingAs($this->user);

            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
            ]);

            $lot->delete();

            expect($lot->fresh()->deleted_by)->toBe($this->user->id);
        });
    });

    describe('casts and attributes', function () {
        it('casts dates properly', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'manufactured_date' => '2024-01-15',
                'expiration_date' => '2025-01-15',
            ]);

            expect($lot->manufactured_date)->toBeInstanceOf(Carbon::class);
            expect($lot->expiration_date)->toBeInstanceOf(Carbon::class);
        });

        it('casts quantities to decimal with precision', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_produced' => 123.4567,
                'quantity_remaining' => 98.7654,
                'unit_cost' => 45.6789,
            ]);

            expect($lot->quantity_produced)->toBeFloat();
            expect($lot->quantity_remaining)->toBeFloat();
            expect($lot->unit_cost)->toBeFloat();
        });

        it('casts arrays properly', function () {
            $qualityAttributes = ['moisture' => '5%', 'purity' => '99%'];
            $metadata = ['warehouse_section' => 'A1', 'inspector' => 'John Doe'];

            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quality_attributes' => $qualityAttributes,
                'metadata' => $metadata,
            ]);

            expect($lot->quality_attributes)->toBeArray();
            expect($lot->metadata)->toBeArray();
            expect($lot->quality_attributes)->toBe($qualityAttributes);
            expect($lot->metadata)->toBe($metadata);
        });

        it('casts boolean values correctly', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'is_active' => true,
            ]);

            expect($lot->is_active)->toBeBool();
            expect($lot->is_active)->toBeTrue();
        });
    });

    describe('route key handling', function () {
        it('uses slug as route key', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'lot_number' => 'TEST-LOT-001',
            ]);

            expect($lot->getRouteKeyName())->toBe('slug');
            expect($lot->getRouteKey())->toBe('test-lot-001');
        });
    });

    describe('edge cases and error handling', function () {
        it('handles null expiration dates gracefully', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'expiration_date' => null,
            ]);

            expect($lot->isExpired())->toBeFalse();
            expect($lot->isExpiringSoon())->toBeFalse();
            expect($lot->daysUntilExpiration())->toBeNull();
        });

        it('handles fractional quantities correctly', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_produced' => 100.25,
                'quantity_remaining' => 75.75,
            ]);

            expect($lot->reduceQuantity(25.5))->toBeTrue();
            expect($lot->fresh()->quantity_remaining)->toBe(50.25);
        });

        it('handles zero and negative quantities appropriately', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_remaining' => 0.0,
            ]);

            expect($lot->isAvailable())->toBeFalse();
            expect($lot->reduceQuantity(1.0))->toBeFalse();
        });

        it('handles lots without movements', function () {
            $lot = ProductLot::factory()->create([
                'product_id' => $this->product->id,
                'quantity_produced' => 100.0,
            ]);

            expect($lot->movements()->count())->toBe(0);
            expect($lot->calculateRemainingQuantity())->toBe(100.0);
        });
    });
});
