<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('InventoryMovement Model', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->user = User::factory()->forCompany($this->company)->create();
        $this->warehouse = Warehouse::factory()->forCompany($this->company)->create();
        $this->product = Product::factory()->forCompany($this->company)->create();
    });

    describe('relationships', function () {
        it('belongs to product', function () {
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->product)->toBeInstanceOf(Product::class);
            expect($movement->product->id)->toBe($this->product->id);
        });

        it('belongs to warehouse', function () {
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->warehouse)->toBeInstanceOf(Warehouse::class);
            expect($movement->warehouse->id)->toBe($this->warehouse->id);
        });

        it('belongs to supplier when applicable', function () {
            $supplier = Supplier::factory()->forCompany($this->company)->create();
            $movement = InventoryMovement::factory()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'supplier_id' => $supplier->id,
                ])
                ->create();

            expect($movement->supplier)->toBeInstanceOf(Supplier::class);
            expect($movement->supplier->id)->toBe($supplier->id);
        });

        it('belongs to customer when applicable', function () {
            $customer = Customer::factory()->forCompany($this->company)->create();
            $movement = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'customer_id' => $customer->id,
                ])
                ->create();

            expect($movement->customer)->toBeInstanceOf(Customer::class);
            expect($movement->customer->id)->toBe($customer->id);
        });

        it('belongs to storage location when specified', function () {
            $storageLocation = StorageLocation::factory()
                ->forCompany($this->company)
                ->forWarehouse($this->warehouse)
                ->create();

            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'storage_location_id' => $storageLocation->id,
                ])
                ->create();

            expect($movement->storageLocation)->toBeInstanceOf(StorageLocation::class);
            expect($movement->storageLocation->id)->toBe($storageLocation->id);
        });

        it('belongs to confirming user', function () {
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'confirmed_by' => $this->user->id,
                ])
                ->create();

            expect($movement->confirmedBy)->toBeInstanceOf(User::class);
            expect($movement->confirmedBy->id)->toBe($this->user->id);
        });

        it('belongs to creator user', function () {
            $this->actingAs($this->user);
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->creator)->toBeInstanceOf(User::class);
            expect($movement->creator->id)->toBe($this->user->id);
        });
    });

    describe('movement types and validation', function () {
        it('creates purchase movement with positive quantity', function () {
            $movement = InventoryMovement::factory()
                ->purchase()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 100,
                ])
                ->create();

            expect($movement->movement_type)->toBe('purchase');
            expect($movement->quantity)->toBeGreaterThan(0);
            expect($movement->document_type)->toBe('invoice');
        });

        it('creates sale movement with negative quantity', function () {
            $movement = InventoryMovement::factory()
                ->sale()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => -50,
                ])
                ->create();

            expect($movement->movement_type)->toBe('sale');
            expect($movement->quantity)->toBeLessThan(0);
            expect($movement->document_type)->toBe('invoice');
        });

        it('creates positive adjustment movement', function () {
            $movement = InventoryMovement::factory()
                ->adjustment(true)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->movement_type)->toBe('adjustment');
            expect($movement->quantity)->toBeGreaterThan(0);
            expect($movement->notes)->toContain('Positive adjustment');
        });

        it('creates negative adjustment movement', function () {
            $movement = InventoryMovement::factory()
                ->adjustment(false)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->movement_type)->toBe('adjustment');
            expect($movement->quantity)->toBeLessThan(0);
            expect($movement->notes)->toContain('Negative adjustment');
        });

        it('creates transfer out movement with negative quantity', function () {
            $toWarehouse = Warehouse::factory()->forCompany($this->company)->create();
            $movement = InventoryMovement::factory()
                ->transferOut()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'to_warehouse_id' => $toWarehouse->id,
                ])
                ->create();

            expect($movement->movement_type)->toBe('transfer_out');
            expect($movement->quantity)->toBeLessThan(0);
            expect($movement->to_warehouse_id)->toBe($toWarehouse->id);
        });

        it('creates transfer in movement with positive quantity', function () {
            $fromWarehouse = Warehouse::factory()->forCompany($this->company)->create();
            $movement = InventoryMovement::factory()
                ->transferIn()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'from_warehouse_id' => $fromWarehouse->id,
                ])
                ->create();

            expect($movement->movement_type)->toBe('transfer_in');
            expect($movement->quantity)->toBeGreaterThan(0);
            expect($movement->from_warehouse_id)->toBe($fromWarehouse->id);
        });

        it('creates damage movement with negative quantity', function () {
            $movement = InventoryMovement::factory()
                ->damage()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->movement_type)->toBe('damage');
            expect($movement->quantity)->toBeLessThan(0);
            expect($movement->notes)->toContain('Damaged goods');
        });

        it('creates expiry movement with negative quantity and past expiration date', function () {
            $movement = InventoryMovement::factory()
                ->expiry()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->movement_type)->toBe('expiry');
            expect($movement->quantity)->toBeLessThan(0);
            expect($movement->expiration_date)->toBeLessThan(now());
            expect($movement->notes)->toContain('Expired goods disposal');
        });
    });

    describe('lot tracking', function () {
        it('creates movement with lot tracking information', function () {
            $lotNumber = 'LOT-TEST-2024';
            $expirationDate = now()->addMonths(6);

            $movement = InventoryMovement::factory()
                ->withLot($lotNumber, $expirationDate)
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->lot_number)->toBe($lotNumber);
            expect($movement->expiration_date->format('Y-m-d'))->toBe($expirationDate->format('Y-m-d'));
        });

        it('generates lot number when none provided', function () {
            $movement = InventoryMovement::factory()
                ->withLot()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->lot_number)->not->toBeNull();
            expect($movement->lot_number)->toMatch('/LOT-\d{2}[A-Z]{2}-\d{4}/');
        });
    });

    describe('confirmation workflow', function () {
        it('creates confirmed movement by default', function () {
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->is_confirmed)->toBeTrue();
            expect($movement->confirmed_at)->not->toBeNull();
            expect($movement->confirmed_by)->not->toBeNull();
        });

        it('creates pending movement when specified', function () {
            $movement = InventoryMovement::factory()
                ->pending()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->is_confirmed)->toBeFalse();
            expect($movement->confirmed_at)->toBeNull();
            expect($movement->confirmed_by)->toBeNull();
        });
    });

    describe('high value movements', function () {
        it('creates high value movement', function () {
            $movement = InventoryMovement::factory()
                ->highValue()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->total_cost)->toBeGreaterThan(10000); // High value threshold
            expect($movement->quantity)->toBeGreaterThan(100);
            expect($movement->unit_cost)->toBeGreaterThan(100);
        });
    });

    describe('transfer movements', function () {
        it('creates transfer pair with matching transfer_id', function () {
            $fromWarehouse = $this->warehouse;
            $toWarehouse = Warehouse::factory()->forCompany($this->company)->create();

            $transferOut = InventoryMovement::factory()
                ->forTransfer($fromWarehouse->id, $toWarehouse->id)
                ->transferOut()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $fromWarehouse->id,
                ])
                ->create();

            $transferIn = InventoryMovement::factory()
                ->forTransfer($fromWarehouse->id, $toWarehouse->id)
                ->transferIn()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $toWarehouse->id,
                    'transfer_id' => $transferOut->transfer_id,
                ])
                ->create();

            expect($transferOut->transfer_id)->toBe($transferIn->transfer_id);
            expect($transferOut->from_warehouse_id)->toBe($fromWarehouse->id);
            expect($transferOut->to_warehouse_id)->toBe($toWarehouse->id);
            expect($transferIn->from_warehouse_id)->toBe($fromWarehouse->id);
            expect($transferIn->to_warehouse_id)->toBe($toWarehouse->id);
        });
    });

    describe('cost calculations', function () {
        it('calculates total cost correctly', function () {
            $quantity = 25.5;
            $unitCost = 12.75;
            $expectedTotal = $quantity * $unitCost;

            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $expectedTotal,
                ])
                ->create();

            expect($movement->total_cost)->toBe($expectedTotal);
        });

        it('handles zero cost movements', function () {
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'unit_cost' => 0,
                    'total_cost' => 0,
                ])
                ->create();

            expect($movement->unit_cost)->toBe(0.0);
            expect($movement->total_cost)->toBe(0.0);
        });
    });

    describe('metadata and reference tracking', function () {
        it('stores metadata as JSON', function () {
            $metadata = [
                'source_system' => 'ERP',
                'batch_id' => 'BATCH-001',
                'custom_fields' => ['field1' => 'value1'],
            ];

            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'metadata' => $metadata,
                ])
                ->create();

            expect($movement->metadata)->toBe($metadata);
        });

        it('generates unique reference numbers', function () {
            $movement1 = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $movement2 = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement1->reference_number)->not->toBe($movement2->reference_number);
        });
    });

    describe('audit trail', function () {
        it('records creator when user is authenticated', function () {
            $this->actingAs($this->user);

            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            expect($movement->created_by)->toBe($this->user->id);
        });

        it('supports soft deletion with deleted_by tracking', function () {
            $this->actingAs($this->user);

            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                ])
                ->create();

            $movementId = $movement->id;
            $movement->delete();

            expect(InventoryMovement::find($movementId))->toBeNull();
            expect(InventoryMovement::withTrashed()->find($movementId))->not->toBeNull();
            expect(InventoryMovement::withTrashed()->find($movementId)->deleted_by)->toBe($this->user->id);
        });
    });

    describe('casts and attributes', function () {
        it('casts quantities to decimal', function () {
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 123.4567,
                    'unit_cost' => 98.7654,
                    'total_cost' => 12098.7654,
                ])
                ->create();

            expect($movement->quantity)->toBeFloat();
            expect($movement->unit_cost)->toBeFloat();
            expect($movement->total_cost)->toBeFloat();
        });

        it('casts dates properly', function () {
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'expiration_date' => '2025-12-31',
                ])
                ->create();

            expect($movement->expiration_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($movement->confirmed_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($movement->active_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        it('casts metadata to array', function () {
            $metadata = ['key' => 'value', 'number' => 123];

            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'metadata' => $metadata,
                ])
                ->create();

            expect($movement->metadata)->toBeArray();
            expect($movement->metadata)->toBe($metadata);
        });
    });

    describe('edge cases', function () {
        it('handles fractional quantities correctly', function () {
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 0.1234,
                ])
                ->create();

            expect($movement->quantity)->toBe(0.1234);
        });

        it('handles zero quantity movements', function () {
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'quantity' => 0,
                ])
                ->create();

            expect($movement->quantity)->toBe(0.0);
        });

        it('handles movements without lot tracking', function () {
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'lot_number' => null,
                    'expiration_date' => null,
                ])
                ->create();

            expect($movement->lot_number)->toBeNull();
            expect($movement->expiration_date)->toBeNull();
        });

        it('handles movements with empty metadata', function () {
            $movement = InventoryMovement::factory()
                ->state([
                    'product_id' => $this->product->id,
                    'warehouse_id' => $this->warehouse->id,
                    'metadata' => [],
                ])
                ->create();

            expect($movement->metadata)->toBe([]);
        });
    });
});
