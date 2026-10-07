<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferDetail;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->forCompany($this->company)->create();
    $this->actingAs($this->user);

    $this->fromWarehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
    $this->toWarehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
    $this->product = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);

    $this->inventory = Inventory::factory()->create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->fromWarehouse->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
        'available_quantity' => 100,
        'unit_cost' => 1.00,
    ]);
});

function transferReasons(): void
{
    MovementReason::factory()->create(['code' => 'TRANSFER_OUT', 'name' => 'Salida por Traslado', 'slug' => 'salida-por-traslado', 'category' => 'transfer', 'movement_type' => 'out']);
    MovementReason::factory()->create(['code' => 'TRANSFER_IN', 'name' => 'Entrada por Traslado', 'slug' => 'entrada-por-traslado', 'category' => 'transfer', 'movement_type' => 'in']);
}

function approvedTransfer(float $quantity, ?float $detailCost = null): InventoryTransfer
{
    $transfer = InventoryTransfer::create([
        'from_warehouse_id' => test()->fromWarehouse->id,
        'to_warehouse_id' => test()->toWarehouse->id,
        'physical_document_number' => (string) fake()->unique()->numberBetween(1000, 9999),
        'document_date' => now()->toDateString(),
        'status' => 'approved',
    ]);

    InventoryTransferDetail::create([
        'transfer_id' => $transfer->id,
        'product_id' => test()->product->id,
        'quantity' => $quantity,
        'unit_cost' => $detailCost,
    ]);

    return $transfer;
}

test('creating a transfer stores the average cost of the origin warehouse and ignores any cost sent by the client', function () {
    Volt::test('inventory.transfers.create')
        ->set('from_warehouse_id', $this->fromWarehouse->id)
        ->set('to_warehouse_id', $this->toWarehouse->id)
        ->set('physical_document_number', '1001')
        ->set('products', [
            ['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => 9.99, 'notes' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $detail = InventoryTransferDetail::query()->where('product_id', $this->product->id)->firstOrFail();

    expect((float) $detail->unit_cost)->toBe(1.0);
});

test('editing a transfer keeps the average cost of the origin warehouse and ignores any cost sent by the client', function () {
    $transfer = InventoryTransfer::create([
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'physical_document_number' => '1003',
        'document_date' => now()->toDateString(),
        'status' => 'pending',
    ]);

    InventoryTransferDetail::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 5,
        'unit_cost' => 1.0,
    ]);

    Volt::test('inventory.transfers.edit', ['transfer' => $transfer])
        ->assertSet('products.0.unit_cost', 1.0)
        ->set('products.0.unit_cost', 7.77)
        ->set('products.0.quantity', 8)
        ->call('save')
        ->assertHasNoErrors();

    $detail = $transfer->fresh()->details->first();

    expect((float) $detail->unit_cost)->toBe(1.0)
        ->and((float) $detail->quantity)->toBe(8.0);
});

test('shipping uses the average cost of the origin warehouse at that moment, not the cost captured when the transfer was created', function () {
    transferReasons();
    $transfer = approvedTransfer(10, 1.0);

    // A purchase arrived after the transfer was created and moved the average
    $this->inventory->update(['unit_cost' => 2.0]);

    expect($transfer->ship($this->user->id))->toBeTrue();

    $movement = InventoryMovement::query()
        ->where('transfer_id', $transfer->id)
        ->where('movement_type', 'transfer_out')
        ->firstOrFail();

    expect((float) $movement->unit_cost)->toBe(2.0)
        ->and((float) $movement->total_cost)->toBe(20.0)
        ->and((float) $transfer->fresh()->details->first()->unit_cost)->toBe(2.0)
        ->and((float) $this->inventory->fresh()->quantity)->toBe(90.0)
        ->and((float) $this->inventory->fresh()->unit_cost)->toBe(2.0);
});

test('a legacy detail without unit cost ships at the average cost of the origin warehouse', function () {
    transferReasons();
    $transfer = approvedTransfer(10, null);

    expect($transfer->ship($this->user->id))->toBeTrue();

    $movement = InventoryMovement::query()
        ->where('transfer_id', $transfer->id)
        ->where('movement_type', 'transfer_out')
        ->firstOrFail();

    expect((float) $movement->unit_cost)->toBe(1.0);
});

test('receiving a transfer averages the incoming cost with the stock already in the destination warehouse', function () {
    transferReasons();

    Inventory::factory()->create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->toWarehouse->id,
        'quantity' => 10,
        'reserved_quantity' => 0,
        'available_quantity' => 10,
        'unit_cost' => 2.0,
    ]);

    $transfer = approvedTransfer(10);

    expect($transfer->ship($this->user->id))->toBeTrue()
        ->and($transfer->fresh()->receive($this->user->id))->toBeTrue();

    $destination = Inventory::query()
        ->where('product_id', $this->product->id)
        ->where('warehouse_id', $this->toWarehouse->id)
        ->firstOrFail();

    // 10 units at 2.00 plus 10 units at 1.00 = 20 units at 1.50
    expect((float) $destination->quantity)->toBe(20.0)
        ->and((float) $destination->unit_cost)->toBe(1.5)
        ->and((float) $destination->total_value)->toBe(30.0);

    $inbound = InventoryMovement::query()
        ->where('transfer_id', $transfer->id)
        ->where('movement_type', 'transfer_in')
        ->firstOrFail();

    expect((float) $inbound->unit_cost)->toBe(1.0)
        ->and((float) $inbound->balance_unit_cost)->toBe(1.5);
});
