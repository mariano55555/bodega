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

function transferOutReason(): MovementReason
{
    return MovementReason::factory()->create([
        'code' => 'TRANSFER_OUT',
        'name' => 'Salida por Traslado',
        'category' => 'transfer',
        'movement_type' => 'out',
    ]);
}

test('creating a transfer stores the unit cost entered by the user instead of the inventory cost', function () {
    Volt::test('inventory.transfers.create')
        ->set('from_warehouse_id', $this->fromWarehouse->id)
        ->set('to_warehouse_id', $this->toWarehouse->id)
        ->set('physical_document_number', '1001')
        ->set('products', [
            ['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => 1.5, 'notes' => ''],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $detail = InventoryTransferDetail::query()->where('product_id', $this->product->id)->firstOrFail();

    expect((float) $detail->unit_cost)->toBe(1.5)
        ->and((float) $this->inventory->fresh()->unit_cost)->toBe(1.0);
});

test('creating a transfer rejects an invalid unit cost', function (mixed $unitCost, string $rule) {
    Volt::test('inventory.transfers.create')
        ->set('from_warehouse_id', $this->fromWarehouse->id)
        ->set('to_warehouse_id', $this->toWarehouse->id)
        ->set('physical_document_number', '1002')
        ->set('products', [
            ['product_id' => $this->product->id, 'quantity' => 5, 'unit_cost' => $unitCost, 'notes' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['products.0.unit_cost' => $rule]);

    expect(InventoryTransfer::query()->count())->toBe(0);
})->with([
    'negative' => [-1, 'min'],
    'text' => ['abc', 'numeric'],
    'empty' => ['', 'required'],
]);

test('editing a transfer loads and keeps the unit cost entered by the user', function () {
    $transfer = InventoryTransfer::create([
        'company_id' => $this->company->id,
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
        'unit_cost' => 1.5,
    ]);

    Volt::test('inventory.transfers.edit', ['transfer' => $transfer])
        ->assertSet('products.0.unit_cost', 1.5)
        ->set('products.0.unit_cost', 2.25)
        ->call('save')
        ->assertHasNoErrors();

    $detail = $transfer->fresh()->details->first();

    expect((float) $detail->unit_cost)->toBe(2.25);
});

test('shipping a transfer uses the detail unit cost and not the current inventory cost', function () {
    transferOutReason();

    $transfer = InventoryTransfer::create([
        'company_id' => $this->company->id,
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'physical_document_number' => '1004',
        'document_date' => now()->toDateString(),
        'status' => 'approved',
    ]);

    InventoryTransferDetail::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_cost' => 1.5,
    ]);

    expect($transfer->ship($this->user->id))->toBeTrue();

    $movement = InventoryMovement::query()
        ->where('transfer_id', $transfer->id)
        ->where('movement_type', 'transfer_out')
        ->firstOrFail();

    expect((float) $movement->unit_cost)->toBe(1.5)
        ->and((float) $movement->total_cost)->toBe(15.0);
});

test('shipping a legacy detail without unit cost falls back to the inventory cost', function () {
    transferOutReason();

    $transfer = InventoryTransfer::create([
        'company_id' => $this->company->id,
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'physical_document_number' => '1005',
        'document_date' => now()->toDateString(),
        'status' => 'approved',
    ]);

    InventoryTransferDetail::create([
        'transfer_id' => $transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 10,
        'unit_cost' => null,
    ]);

    expect($transfer->ship($this->user->id))->toBeTrue();

    $movement = InventoryMovement::query()
        ->where('transfer_id', $transfer->id)
        ->where('movement_type', 'transfer_out')
        ->firstOrFail();

    expect((float) $movement->unit_cost)->toBe(1.0);
});
