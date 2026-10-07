<?php

declare(strict_types=1);

use App\Exceptions\InventoryReversalException;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\InventoryClosure;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferDetail;
use App\Models\MovementReason;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->forCompany($this->company)->create();
    $this->actingAs($this->user);

    $this->fromWarehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Bodega Central']);
    $this->toWarehouse = Warehouse::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Bodega Cocina']);
    $this->product = Product::factory()->create(['company_id' => $this->company->id, 'is_active' => true, 'name' => 'Arroz']);

    MovementReason::factory()->create(['code' => 'TRANSFER_OUT', 'name' => 'Salida por Traslado', 'slug' => 'salida-por-traslado', 'category' => 'transfer', 'movement_type' => 'out']);
    MovementReason::factory()->create(['code' => 'TRANSFER_IN', 'name' => 'Entrada por Traslado', 'slug' => 'entrada-por-traslado', 'category' => 'transfer', 'movement_type' => 'in']);

    $this->origin = Inventory::factory()->create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->fromWarehouse->id,
        'quantity' => 100,
        'reserved_quantity' => 0,
        'available_quantity' => 100,
        'unit_cost' => 1.25,
    ]);

    $this->transfer = InventoryTransfer::create([
        'from_warehouse_id' => $this->fromWarehouse->id,
        'to_warehouse_id' => $this->toWarehouse->id,
        'physical_document_number' => '5001',
        'document_date' => now()->toDateString(),
        'status' => 'approved',
    ]);

    InventoryTransferDetail::create([
        'transfer_id' => $this->transfer->id,
        'product_id' => $this->product->id,
        'quantity' => 40,
    ]);
});

function destinationInventory(): ?Inventory
{
    return Inventory::query()
        ->where('product_id', test()->product->id)
        ->where('warehouse_id', test()->toWarehouse->id)
        ->first();
}

test('cancelling a transfer that has not shipped only changes its status', function () {
    expect($this->transfer->cancel($this->user->id, 'Error de captura'))->toBeTrue();

    $transfer = $this->transfer->fresh();

    expect($transfer->status)->toBe('cancelled')
        ->and($transfer->cancelled_by)->toBe($this->user->id)
        ->and($transfer->cancellation_reason)->toBe('Error de captura')
        ->and(InventoryMovement::query()->where('transfer_id', $transfer->id)->count())->toBe(0)
        ->and((float) $this->origin->fresh()->quantity)->toBe(100.0);
});

test('cancelling a shipped transfer returns the stock to the origin warehouse at the cost it left with', function () {
    expect($this->transfer->ship($this->user->id))->toBeTrue()
        ->and((float) $this->origin->fresh()->quantity)->toBe(60.0);

    expect($this->transfer->fresh()->cancel($this->user->id))->toBeTrue();

    $origin = $this->origin->fresh();

    expect($origin->quantity)->toEqual(100)
        ->and((float) $origin->unit_cost)->toBe(1.25)
        ->and($this->transfer->fresh()->status)->toBe('cancelled');

    $reversal = InventoryMovement::query()
        ->where('transfer_id', $this->transfer->id)
        ->where('document_type', 'transfer_cancellation')
        ->firstOrFail();

    expect($reversal->movement_type)->toBe('transfer_in')
        ->and((int) $reversal->warehouse_id)->toBe($this->fromWarehouse->id)
        ->and((float) $reversal->quantity_in)->toBe(40.0)
        ->and((float) $reversal->unit_cost)->toBe(1.25)
        ->and((float) $reversal->balance_unit_cost)->toBe(1.25);
});

test('cancelling a received transfer removes the stock from the destination and returns it to the origin', function () {
    expect($this->transfer->ship($this->user->id))->toBeTrue()
        ->and($this->transfer->fresh()->receive($this->user->id))->toBeTrue()
        ->and((float) destinationInventory()->quantity)->toBe(40.0);

    expect($this->transfer->fresh()->cancel($this->user->id, 'Bodega destino equivocada'))->toBeTrue();

    expect($this->origin->fresh()->quantity)->toEqual(100)
        ->and(destinationInventory()->quantity)->toEqual(0)
        ->and($this->transfer->fresh()->status)->toBe('cancelled');

    $reversals = InventoryMovement::query()
        ->where('transfer_id', $this->transfer->id)
        ->where('document_type', 'transfer_cancellation')
        ->get();

    expect($reversals)->toHaveCount(2)
        ->and($reversals->where('movement_type', 'transfer_out')->first()->warehouse_id)->toBe($this->toWarehouse->id)
        ->and($reversals->where('movement_type', 'transfer_in')->first()->warehouse_id)->toBe($this->fromWarehouse->id);
});

test('a received transfer cannot be cancelled when the destination already consumed the stock', function () {
    expect($this->transfer->ship($this->user->id))->toBeTrue()
        ->and($this->transfer->fresh()->receive($this->user->id))->toBeTrue();

    destinationInventory()->update(['quantity' => 15, 'available_quantity' => 15]);

    $transfer = $this->transfer->fresh();

    expect(fn () => $transfer->cancel($this->user->id))
        ->toThrow(InventoryReversalException::class, 'Bodega Cocina');

    expect($this->transfer->fresh()->status)->toBe('received')
        ->and((float) $this->origin->fresh()->quantity)->toBe(60.0)
        ->and((float) destinationInventory()->quantity)->toBe(15.0)
        ->and(InventoryMovement::query()->where('transfer_id', $this->transfer->id)->where('document_type', 'transfer_cancellation')->count())->toBe(0);
});

test('a shipped transfer cannot be cancelled when the origin warehouse period is closed', function () {
    expect($this->transfer->ship($this->user->id))->toBeTrue();

    InventoryClosure::factory()->create([
        'company_id' => $this->company->id,
        'warehouse_id' => $this->fromWarehouse->id,
        'year' => now()->year,
        'month' => now()->month,
        'status' => 'cerrado',
    ]);

    $transfer = $this->transfer->fresh();

    expect(fn () => $transfer->cancel($this->user->id))
        ->toThrow(InventoryReversalException::class, 'cierre mensual');

    expect($this->transfer->fresh()->status)->toBe('in_transit')
        ->and((float) $this->origin->fresh()->quantity)->toBe(60.0);
});

test('an already cancelled transfer cannot be cancelled again', function () {
    expect($this->transfer->cancel($this->user->id))->toBeTrue()
        ->and($this->transfer->fresh()->cancel($this->user->id))->toBeFalse();
});
